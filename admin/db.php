<?php
declare(strict_types=1);

// MySQL connection + content CRUD. Default credentials match a stock XAMPP
// install (root, no password). Change here if your MySQL is configured
// differently.
const DB_HOST = 'localhost';
const DB_NAME = 'nethra-portfolio';
const DB_USER = 'root';
const DB_PASS = '';
// const DB_NAME = 'u269004420_nethra';
// const DB_USER = 'u269004420_nethra';
// const DB_PASS = 'g!J0!h4Ru$';

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    db_ensure_schema($pdo);
    return $pdo;
}

// Safety net so the site still works against a fresh MySQL instance even if
// data/schema.sql was never imported by hand in phpMyAdmin.
function db_ensure_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS `cases` (
        `id` VARCHAR(191) NOT NULL,
        `slug` VARCHAR(191) NOT NULL DEFAULT '',
        `title` VARCHAR(120) NOT NULL,
        `tag` VARCHAR(60) NOT NULL DEFAULT '',
        `blurb` VARCHAR(400) NOT NULL DEFAULT '',
        `headline` VARCHAR(400) NOT NULL DEFAULT '',
        `lede` TEXT NULL,
        `chips` JSON NULL,
        `meta` JSON NULL,
        `pillars` JSON NULL,
        `chapters` JSON NULL,
        `metrics` JSON NULL,
        `slot` VARCHAR(191) NOT NULL,
        `src` VARCHAR(255) NOT NULL DEFAULT '',
        `banner_src` VARCHAR(255) NOT NULL DEFAULT '',
        `ph` VARCHAR(160) NOT NULL DEFAULT '',
        `meta_title` VARCHAR(200) NOT NULL DEFAULT '',
        `meta_description` VARCHAR(300) NOT NULL DEFAULT '',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug_unique` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `playground_items` (
        `id` VARCHAR(191) NOT NULL,
        `title` VARCHAR(120) NOT NULL,
        `tag` VARCHAR(60) NOT NULL DEFAULT '',
        `description` VARCHAR(300) NOT NULL DEFAULT '',
        `likes` VARCHAR(10) NOT NULL DEFAULT '0',
        `views` VARCHAR(10) NOT NULL DEFAULT '0',
        `slot` VARCHAR(191) NOT NULL,
        `src` VARCHAR(255) NOT NULL DEFAULT '',
        `ph` VARCHAR(160) NOT NULL DEFAULT '',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ai_explorations` (
        `id` VARCHAR(191) NOT NULL,
        `title` VARCHAR(120) NOT NULL,
        `youtube_url` VARCHAR(500) NOT NULL DEFAULT '',
        `video_id` VARCHAR(32) NOT NULL DEFAULT '',
        `slot` VARCHAR(191) NOT NULL,
        `src` VARCHAR(255) NOT NULL DEFAULT '',
        `ph` VARCHAR(160) NOT NULL DEFAULT '',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function db_json_or(?string $raw, $fallback) {
    if ($raw === null) return $fallback;
    $v = json_decode($raw, true);
    return $v === null && json_last_error() !== JSON_ERROR_NONE ? $fallback : $v;
}

// The admin chapter editor shows up to 4 images per chapter regardless of
// whether the chapter already uses the new flat `images` list or still has
// the original hand-authored named-slot fields (src/src1b/src2/src2b/src2c/
// src3) — this normalizes either shape into a plain list for display, so
// editing an old chapter still shows (and can keep) its existing images.
// Returns ['images' => first-group images, 'more' => [['sub', 'images'], ...]]
// for the admin form. Admin-saved chapters carry `images` + `more`;
// hand-authored ones use named slots — src/src1b under `sub`,
// src2/src2b/src2c under `sub2`, src3 under `sub3` — mapped to the same shape
// so editing them keeps every sub-heading.
function chapter_groups_for_display(array $ch): array {
    $pick = fn($list) => is_array($list) ? array_slice(array_values(array_map(fn($im) => ['src' => $im['src'] ?? ''], $list)), 0, 4) : [];
    if (isset($ch['images']) && is_array($ch['images'])) {
        $more = [];
        foreach ((is_array($ch['more'] ?? null) ? $ch['more'] : []) as $g) {
            $more[] = ['sub' => (string)($g['sub'] ?? ''), 'images' => $pick($g['images'] ?? [])];
        }
        return ['images' => $pick($ch['images']), 'more' => $more];
    }
    $legacy = function (array $keys) use ($ch) {
        $out = [];
        foreach ($keys as $k) if (!empty($ch[$k])) $out[] = ['src' => $ch[$k]];
        return $out;
    };
    $more = [];
    foreach ([['sub2', ['src2', 'src2b', 'src2c']], ['sub3', ['src3']]] as [$subKey, $keys]) {
        $imgs = $legacy($keys);
        if (!empty($ch[$subKey]) || $imgs) $more[] = ['sub' => (string)($ch[$subKey] ?? ''), 'images' => $imgs];
    }
    return ['images' => $legacy(['src', 'src1b']), 'more' => $more];
}

// The eyebrow shown above a chapter's title on the case page is stored in
// ch.n as "NN / eyebrow". Parse the existing eyebrow back out for prefill,
// but not if it's just a duplicate of the title (a chapter saved before
// this field existed has ch.n auto-set to "NN / {title}") — showing that
// back as if it were a real value would just perpetuate the duplication
// this field exists to fix, so those prefill blank instead.
function chapter_eyebrow_for_display(array $ch): string {
    $n = (string)($ch['n'] ?? '');
    $slash = strpos($n, '/');
    if ($slash === false) return '';
    $eyebrow = trim(substr($n, $slash + 1));
    if ($eyebrow !== '' && mb_strtolower($eyebrow) === mb_strtolower(trim((string)($ch['title'] ?? '')))) {
        return '';
    }
    return $eyebrow;
}

function db_row_to_case(array $r): array {
    return [
        'id' => $r['id'],
        'slug' => ($r['slug'] ?? '') !== '' ? $r['slug'] : $r['id'],
        'title' => $r['title'],
        'tag' => $r['tag'],
        'blurb' => $r['blurb'],
        'headline' => $r['headline'] !== '' ? $r['headline'] : $r['title'],
        'lede' => $r['lede'] !== null && $r['lede'] !== '' ? $r['lede'] : $r['blurb'],
        'chips' => db_json_or($r['chips'], []),
        'meta' => db_json_or($r['meta'], []),
        'pillars' => db_json_or($r['pillars'], null),
        'chapters' => db_json_or($r['chapters'], []),
        'metrics' => db_json_or($r['metrics'], []),
        'slot' => $r['slot'],
        'src' => $r['src'],
        'bannerSrc' => $r['banner_src'] ?? '',
        'ph' => $r['ph'],
        'metaTitle' => $r['meta_title'] ?? '',
        'metaDescription' => $r['meta_description'] ?? '',
        'createdAt' => $r['created_at'],
    ];
}

function db_row_to_pg(array $r): array {
    return [
        'id' => $r['id'],
        'title' => $r['title'],
        'tag' => $r['tag'],
        'desc' => $r['description'],
        'likes' => $r['likes'],
        'views' => $r['views'],
        'slot' => $r['slot'],
        'src' => $r['src'],
        'ph' => $r['ph'],
        'createdAt' => $r['created_at'],
    ];
}

function db_unique_id(string $table, string $base): string {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id FROM `$table` WHERE id = ?");
    $id = $base;
    $n = 2;
    while (true) {
        $stmt->execute([$id]);
        if (!$stmt->fetch()) return $id;
        $id = $base . '-' . $n;
        $n++;
    }
}

// Same idea as db_unique_id() but against the `slug` column, and excluding
// the row being edited (so keeping a case's own current slug unchanged
// never falsely collides with itself).
function db_unique_slug(string $base, string $excludeId = ''): string {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM `cases` WHERE slug = ? AND id != ?');
    $slug = $base;
    $n = 2;
    while (true) {
        $stmt->execute([$slug, $excludeId]);
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . $n;
        $n++;
    }
}

// ── Cases ───────────────────────────────────────────────────────────────
function db_list_cases(): array {
    $rows = db()->query('SELECT * FROM `cases` ORDER BY created_at ASC')->fetchAll();
    return array_map('db_row_to_case', $rows);
}

// Admin-panel "add a case study" only ever supplies the simple listing
// fields; headline/lede default from title/blurb and the rich fields
// (meta/pillars/chapters/metrics) are left null/empty so the case-detail
// page renders a simple page instead of a full written case study. Seed
// full case studies directly via SQL/phpMyAdmin — see data/schema.sql.
function db_insert_case(array $c): void {
    $stmt = db()->prepare('INSERT INTO `cases` (id, slug, title, tag, blurb, headline, lede, chips, meta, pillars, chapters, metrics, slot, src, banner_src, ph, meta_title, meta_description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $c['id'], $c['slug'] ?? $c['id'], $c['title'], $c['tag'], $c['blurb'],
        $c['headline'] ?? $c['title'],
        $c['lede'] ?? $c['blurb'],
        json_encode($c['chips'] ?? []),
        json_encode($c['meta'] ?? []),
        isset($c['pillars']) ? json_encode($c['pillars']) : null,
        json_encode($c['chapters'] ?? []),
        json_encode($c['metrics'] ?? []),
        $c['slot'], $c['src'], $c['bannerSrc'] ?? '', $c['ph'],
        $c['metaTitle'] ?? '', $c['metaDescription'] ?? '',
    ]);
}

function db_update_case(string $id, array $fields): bool {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM `cases` WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) return false;

    $sets = [];
    $params = [];
    foreach (['title', 'tag', 'blurb', 'ph', 'headline', 'lede'] as $k) {
        if (array_key_exists($k, $fields)) { $sets[] = "`$k` = ?"; $params[] = $fields[$k]; }
    }
    if (array_key_exists('chips', $fields)) { $sets[] = '`chips` = ?'; $params[] = json_encode($fields['chips']); }
    if (array_key_exists('meta', $fields)) { $sets[] = '`meta` = ?'; $params[] = json_encode($fields['meta']); }
    if (array_key_exists('pillars', $fields)) { $sets[] = '`pillars` = ?'; $params[] = $fields['pillars'] === null ? null : json_encode($fields['pillars']); }
    if (array_key_exists('metrics', $fields)) { $sets[] = '`metrics` = ?'; $params[] = json_encode($fields['metrics']); }
    if (array_key_exists('chapters', $fields)) { $sets[] = '`chapters` = ?'; $params[] = json_encode($fields['chapters']); }
    if (array_key_exists('src', $fields)) { $sets[] = '`src` = ?'; $params[] = $fields['src']; }
    if (array_key_exists('bannerSrc', $fields)) { $sets[] = '`banner_src` = ?'; $params[] = $fields['bannerSrc']; }
    if (array_key_exists('slug', $fields)) { $sets[] = '`slug` = ?'; $params[] = $fields['slug']; }
    if (array_key_exists('metaTitle', $fields)) { $sets[] = '`meta_title` = ?'; $params[] = $fields['metaTitle']; }
    if (array_key_exists('metaDescription', $fields)) { $sets[] = '`meta_description` = ?'; $params[] = $fields['metaDescription']; }
    if (!$sets) return true;
    $params[] = $id;
    $pdo->prepare('UPDATE `cases` SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
    return true;
}

function db_delete_case(string $id): void {
    db()->prepare('DELETE FROM `cases` WHERE id = ?')->execute([$id]);
}

// ── Playground items ───────────────────────────────────────────────────
function db_list_playground(): array {
    $rows = db()->query('SELECT * FROM `playground_items` ORDER BY created_at ASC')->fetchAll();
    return array_map('db_row_to_pg', $rows);
}

function db_insert_playground(array $it): void {
    $stmt = db()->prepare('INSERT INTO `playground_items` (id, title, tag, description, likes, views, slot, src, ph) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$it['id'], $it['title'], $it['tag'], $it['desc'], $it['likes'], $it['views'], $it['slot'], $it['src'], $it['ph']]);
}

function db_update_playground(string $id, array $fields): bool {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM `playground_items` WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) return false;

    $map = ['title' => 'title', 'tag' => 'tag', 'desc' => 'description', 'likes' => 'likes', 'views' => 'views', 'ph' => 'ph', 'src' => 'src'];
    $sets = [];
    $params = [];
    foreach ($map as $inKey => $col) {
        if (array_key_exists($inKey, $fields)) { $sets[] = "`$col` = ?"; $params[] = $fields[$inKey]; }
    }
    if (!$sets) return true;
    $params[] = $id;
    $pdo->prepare('UPDATE `playground_items` SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
    return true;
}

function db_delete_playground(string $id): void {
    db()->prepare('DELETE FROM `playground_items` WHERE id = ?')->execute([$id]);
}

// ── AI Explorations (YouTube link cards) ───────────────────────────────
// Accepts a full youtube.com/watch, youtu.be, /embed/, /shorts/ link (with
// any extra query params like ?si=... or &t=...) or a bare video id, and
// returns just the 11(+)-char id, or '' if nothing recognizable was found.
function youtube_extract_id(string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~', $url, $m)) return $m[1];
    if (preg_match('~youtube(?:-nocookie)?\.com/(?:embed|shorts|v)/([A-Za-z0-9_-]{6,})~', $url, $m)) return $m[1];
    if (preg_match('~[?&]v=([A-Za-z0-9_-]{6,})~', $url, $m)) return $m[1];
    if (preg_match('~^[A-Za-z0-9_-]{6,}$~', $url)) return $url;
    return '';
}

// YouTube always serves this size for every public video, no API key
// needed — used as the automatic cover image unless the admin uploads one.
function youtube_thumb_url(string $videoId): string {
    return 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg';
}

function db_row_to_ai(array $r): array {
    return [
        'id' => $r['id'],
        'title' => $r['title'],
        'youtubeUrl' => $r['youtube_url'],
        'videoId' => $r['video_id'],
        'slot' => $r['slot'],
        'src' => $r['src'],
        'ph' => $r['ph'],
        'createdAt' => $r['created_at'],
    ];
}

function db_list_ai_explorations(): array {
    $rows = db()->query('SELECT * FROM `ai_explorations` ORDER BY created_at ASC')->fetchAll();
    return array_map('db_row_to_ai', $rows);
}

function db_insert_ai_exploration(array $it): void {
    $stmt = db()->prepare('INSERT INTO `ai_explorations` (id, title, youtube_url, video_id, slot, src, ph) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$it['id'], $it['title'], $it['youtubeUrl'], $it['videoId'], $it['slot'], $it['src'], $it['ph']]);
}

function db_update_ai_exploration(string $id, array $fields): bool {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM `ai_explorations` WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) return false;

    $map = ['title' => 'title', 'youtubeUrl' => 'youtube_url', 'videoId' => 'video_id', 'src' => 'src'];
    $sets = [];
    $params = [];
    foreach ($map as $inKey => $col) {
        if (array_key_exists($inKey, $fields)) { $sets[] = "`$col` = ?"; $params[] = $fields[$inKey]; }
    }
    if (!$sets) return true;
    $params[] = $id;
    $pdo->prepare('UPDATE `ai_explorations` SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
    return true;
}

function db_delete_ai_exploration(string $id): void {
    db()->prepare('DELETE FROM `ai_explorations` WHERE id = ?')->execute([$id]);
}
