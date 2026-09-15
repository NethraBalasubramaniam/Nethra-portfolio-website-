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
function chapter_images_for_display(array $ch): array {
    if (!empty($ch['images']) && is_array($ch['images'])) {
        return array_values(array_map(fn($im) => ['src' => $im['src'] ?? ''], $ch['images']));
    }
    $out = [];
    foreach (['src', 'src1b', 'src2', 'src2b', 'src2c', 'src3'] as $k) {
        if (!empty($ch[$k])) $out[] = ['src' => $ch[$k]];
    }
    return array_slice($out, 0, 4);
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
