<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

define('ADMIN_ROOT', __DIR__);
define('UPLOADS_DIR', dirname(__DIR__) . '/uploads');
define('UPLOADS_URL', 'uploads');

function admin_config(): array {
    return require ADMIN_ROOT . '/config.php';
}

function is_logged_in(): bool {
    return !empty($_SESSION['admin_user']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf(): void {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die('Your session expired or the form was resubmitted. Go back and try again.');
    }
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Admin pages live one directory below site root, so a stored relative path
// like "uploads/admin-....jpg" needs "../" to resolve — but a stored
// absolute URL (e.g. an auto-fetched YouTube thumbnail) must NOT get that
// prefix or it breaks. Every <img> preview in admin that renders a stored
// `src` should go through this instead of hand-rolling the "../" prefix.
function admin_img_src(string $src): string {
    if ($src === '') return '';
    if (preg_match('~^https?://~i', $src)) return $src;
    return '../' . $src;
}
