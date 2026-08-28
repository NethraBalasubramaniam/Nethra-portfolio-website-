<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
check_csrf();

function handle_upload(?array $file): ?string {
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die('Image upload failed (error code ' . (int)$file['error'] . ').');
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        die('Image too large — max 8MB.');
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info) {
        die('That file is not a valid image.');
    }
    $extByMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $mime = $info['mime'] ?? '';
    if (!isset($extByMime[$mime])) {
        die('Unsupported image type — use JPG, PNG, WebP, or GIF.');
    }
    $ext = $extByMime[$mime];
    $name = 'admin-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOADS_DIR . '/' . $name;
    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0777, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        die('Could not save the uploaded image.');
    }
    return UPLOADS_URL . '/' . $name;
}

// PHP groups an array-named file input ($_FILES['pillar_image'][]) by
// property (name/type/tmp_name/error/size), each itself indexed by row —
// this reshapes that into a list of ordinary single-file arrays so
// handle_upload() can take one per row like it already does for the cover
// image.
function normalize_files(array $filesField): array {
    $count = count($filesField['name'] ?? []);
    $out = [];
    for ($i = 0; $i < $count; $i++) {
        $out[$i] = [
            'name' => $filesField['name'][$i] ?? '',
            'type' => $filesField['type'][$i] ?? '',
            'tmp_name' => $filesField['tmp_name'][$i] ?? '',
            'error' => $filesField['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $filesField['size'][$i] ?? 0,
        ];
    }
    return $out;
}

function clean_text($v, int $max = 2000): string {
    $v = trim((string)$v);
    return mb_substr($v, 0, $max);
}

function split_chips($v): array {
    $parts = array_map('trim', explode(',', (string)$v));
    $parts = array_filter($parts, fn($p) => $p !== '');
    return array_values($parts);
}

// Meta / pillars / metrics all arrive as parallel arrays from repeatable
// form rows (meta_k[]/meta_v[], pillar_t[]/pillar_b[], metric_n[]/...) —
// zip them back into row objects and drop any row left fully blank.
function build_meta(): array {
    $ks = $_POST['meta_k'] ?? [];
    $vs = $_POST['meta_v'] ?? [];
    $out = [];
    for ($i = 0; $i < count($ks); $i++) {
        $k = trim((string)($ks[$i] ?? ''));
        $v = trim((string)($vs[$i] ?? ''));
        if ($k === '' && $v === '') continue;
        $out[] = ['k' => mb_substr($k, 0, 60), 'v' => mb_substr($v, 0, 200)];
    }
    return $out;
}

function build_pillars(): ?array {
    $ts = $_POST['pillar_t'] ?? [];
    $bs = $_POST['pillar_b'] ?? [];
    $existingSrcs = $_POST['pillar_src'] ?? [];
    $files = isset($_FILES['pillar_image']) ? normalize_files($_FILES['pillar_image']) : [];
    $out = [];
    for ($i = 0; $i < count($ts); $i++) {
        $t = trim((string)($ts[$i] ?? ''));
        $b = trim((string)($bs[$i] ?? ''));
        // A new upload for this row wins; otherwise keep whatever image the
        // row already had (carried in the hidden pillar_src[] field).
        $uploaded = handle_upload($files[$i] ?? null);
        $src = $uploaded ?? (string)($existingSrcs[$i] ?? '');
        if ($t === '' && $b === '' && $src === '') continue;
        $out[] = [
            'num' => str_pad((string)(count($out) + 1), 2, '0', STR_PAD_LEFT),
            't' => mb_substr($t, 0, 120),
            'b' => mb_substr($b, 0, 300),
            'src' => $src,
            'ph' => mb_substr($t !== '' ? $t : 'Pillar image', 0, 160),
        ];
    }
    // null (not []) so the "What the platform does" section stays hidden
    // when there are no pillars — matches how the case-detail page gates it.
    return $out ? $out : null;
}

function build_metrics(): array {
    $ns = $_POST['metric_n'] ?? [];
    $sufs = $_POST['metric_suffix'] ?? [];
    $labels = $_POST['metric_label'] ?? [];
    $out = [];
    for ($i = 0; $i < count($ns); $i++) {
        $n = trim((string)($ns[$i] ?? ''));
        $suf = trim((string)($sufs[$i] ?? ''));
        $label = trim((string)($labels[$i] ?? ''));
        if ($n === '' && $suf === '' && $label === '') continue;
        $out[] = ['n' => is_numeric($n) ? (strpos($n, '.') !== false ? (float)$n : (int)$n) : 0, 'suffix' => mb_substr($suf, 0, 20), 'label' => mb_substr($label, 0, 160)];
    }
    return $out;
}

// Chapters are rows of chapter_title[]/chapter_sub[]/chapter_paras[]/
// chapter_pull[] (parallel arrays, same pattern as pillars/metrics) plus up
// to 4 fixed image slots per chapter (chapter_image1[].. chapter_image4[],
// each with a matching chapter_imageN_src[] hidden field carrying the
// existing image forward when no new file is uploaded for that slot).
// Fixed slots — not a dynamically-added list — keep every field a simple
// parallel array in lockstep with chapter_title[], with no index bookkeeping
// needed when a chapter row is removed. This intentionally produces a
// simpler `images` list than the original hand-authored chapters (which
// also had secondary sub-headings before some images) — editing an existing
// rich chapter through this form flattens it to this simpler shape.
function build_chapters(): array {
    $titles = $_POST['chapter_title'] ?? [];
    $subs = $_POST['chapter_sub'] ?? [];
    $parasRaw = $_POST['chapter_paras'] ?? [];
    $pulls = $_POST['chapter_pull'] ?? [];
    $imgSrcs = [];
    $imgFiles = [];
    for ($n = 1; $n <= 4; $n++) {
        $imgSrcs[$n] = $_POST["chapter_image{$n}_src"] ?? [];
        $imgFiles[$n] = isset($_FILES["chapter_image{$n}"]) ? normalize_files($_FILES["chapter_image{$n}"]) : [];
    }

    $out = [];
    for ($i = 0; $i < count($titles); $i++) {
        $title = trim((string)($titles[$i] ?? ''));
        $sub = trim((string)($subs[$i] ?? ''));
        $pull = trim((string)($pulls[$i] ?? ''));
        $parasText = (string)($parasRaw[$i] ?? '');
        $paras = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $parasText)), fn($p) => $p !== ''));

        $images = [];
        for ($n = 1; $n <= 4; $n++) {
            $uploaded = handle_upload($imgFiles[$n][$i] ?? null);
            $src = $uploaded ?? (string)($imgSrcs[$n][$i] ?? '');
            if ($src !== '') {
                $images[] = ['slot' => 'chapter-img-' . bin2hex(random_bytes(4)), 'src' => $src, 'ph' => $title !== '' ? $title : 'Chapter image'];
            }
        }

        if ($title === '' && !$paras && $pull === '' && !$images) continue;

        $chapter = [
            'n' => str_pad((string)(count($out) + 1), 2, '0', STR_PAD_LEFT) . ' / ' . ($title !== '' ? $title : 'Chapter'),
            'title' => mb_substr($title, 0, 200),
            'paras' => array_map(fn($p) => mb_substr($p, 0, 2000), $paras),
            'images' => $images,
        ];
        if ($sub !== '') $chapter['sub'] = mb_substr($sub, 0, 160);
        if ($pull !== '') $chapter['pull'] = mb_substr($pull, 0, 400);
        $out[] = $chapter;
    }
    return $out;
}

$action = (string)($_POST['action'] ?? '');

switch ($action) {
    case 'create_case': {
        $title = clean_text($_POST['title'] ?? '', 120);
        if ($title === '') die('Title is required.');
        $id = db_unique_id('cases', slugify($title));
        $src = handle_upload($_FILES['image'] ?? null);
        db_insert_case([
            'id' => $id,
            'title' => $title,
            'tag' => clean_text($_POST['tag'] ?? '', 60),
            'blurb' => clean_text($_POST['blurb'] ?? '', 400),
            'headline' => clean_text($_POST['headline'] ?? '', 400),
            'lede' => clean_text($_POST['lede'] ?? '', 2000),
            'chips' => split_chips($_POST['chips'] ?? ''),
            'meta' => build_meta(),
            'pillars' => build_pillars(),
            'metrics' => build_metrics(),
            'chapters' => build_chapters(),
            'slot' => 'admin-case-' . $id,
            'src' => $src ?? '',
            'ph' => clean_text($_POST['ph'] ?? $title, 160),
        ]);
        break;
    }

    case 'update_case': {
        // Chapters are deliberately NOT included here — see
        // update_case_chapters below and the comment atop
        // _case_chapters_fields.php for why they're a separate save.
        $id = (string)($_POST['id'] ?? '');
        $src = handle_upload($_FILES['image'] ?? null);
        $fields = [
            'title' => clean_text($_POST['title'] ?? '', 120),
            'tag' => clean_text($_POST['tag'] ?? '', 60),
            'blurb' => clean_text($_POST['blurb'] ?? '', 400),
            'headline' => clean_text($_POST['headline'] ?? '', 400),
            'lede' => clean_text($_POST['lede'] ?? '', 2000),
            'chips' => split_chips($_POST['chips'] ?? ''),
            'meta' => build_meta(),
            'pillars' => build_pillars(),
            'metrics' => build_metrics(),
        ];
        if ($src) $fields['src'] = $src;
        if (!db_update_case($id, $fields)) die('Case study not found.');
        break;
    }

    case 'update_case_chapters': {
        $id = (string)($_POST['id'] ?? '');
        if (!db_update_case($id, ['chapters' => build_chapters()])) die('Case study not found.');
        break;
    }

    case 'delete_case': {
        db_delete_case((string)($_POST['id'] ?? ''));
        break;
    }

    case 'create_playground': {
        $title = clean_text($_POST['title'] ?? '', 120);
        if ($title === '') die('Title is required.');
        $id = db_unique_id('playground_items', slugify($title));
        $src = handle_upload($_FILES['image'] ?? null);
        db_insert_playground([
            'id' => $id,
            'title' => $title,
            'tag' => clean_text($_POST['tag'] ?? '', 60),
            'desc' => clean_text($_POST['desc'] ?? '', 300),
            'likes' => clean_text($_POST['likes'] ?? '0', 10),
            'views' => clean_text($_POST['views'] ?? '0', 10),
            'slot' => 'admin-pg-' . $id,
            'src' => $src ?? '',
            'ph' => clean_text($_POST['ph'] ?? $title, 160),
        ]);
        break;
    }

    case 'update_playground': {
        $id = (string)($_POST['id'] ?? '');
        $src = handle_upload($_FILES['image'] ?? null);
        $fields = [
            'title' => clean_text($_POST['title'] ?? '', 120),
            'tag' => clean_text($_POST['tag'] ?? '', 60),
            'desc' => clean_text($_POST['desc'] ?? '', 300),
            'likes' => clean_text($_POST['likes'] ?? '0', 10),
            'views' => clean_text($_POST['views'] ?? '0', 10),
        ];
        if ($src) $fields['src'] = $src;
        if (!db_update_playground($id, $fields)) die('Playground item not found.');
        break;
    }

    case 'delete_playground': {
        db_delete_playground((string)($_POST['id'] ?? ''));
        break;
    }

    default:
        http_response_code(400);
        die('Unknown action.');
}

$allowedRedirects = ['index.php', 'cases.php', 'case-add.php', 'playground.php', 'playground-add.php'];
$redirect = (string)($_POST['redirect'] ?? '');
header('Location: ' . (in_array($redirect, $allowedRedirects, true) ? $redirect : 'index.php'));
exit;
