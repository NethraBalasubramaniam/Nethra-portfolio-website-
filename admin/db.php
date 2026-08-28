<?php
declare(strict_types=1);

// MySQL connection + content CRUD. Default credentials match a stock XAMPP
// install (root, no password). Change here if your MySQL is configured
// differently.
const DB_HOST = 'localhost';
// const DB_NAME = 'nethra-portfolio';
// const DB_USER = 'root';
// const DB_PASS = '';
const DB_NAME = 'u269004420_nethra';
const DB_USER = 'u269004420_nethra';
const DB_PASS = 'g!J0!h4Ru$';

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
        `ph` VARCHAR(160) NOT NULL DEFAULT '',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
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

function db_row_to_case(array $r): array {
    return [
        'id' => $r['id'],
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
        'ph' => $r['ph'],
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
    $stmt = db()->prepare('INSERT INTO `cases` (id, title, tag, blurb, headline, lede, chips, meta, pillars, chapters, metrics, slot, src, ph)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $c['id'], $c['title'], $c['tag'], $c['blurb'],
        $c['headline'] ?? $c['title'],
        $c['lede'] ?? $c['blurb'],
        json_encode($c['chips'] ?? []),
        json_encode($c['meta'] ?? []),
        isset($c['pillars']) ? json_encode($c['pillars']) : null,
        json_encode($c['chapters'] ?? []),
        json_encode($c['metrics'] ?? []),
        $c['slot'], $c['src'], $c['ph'],
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
