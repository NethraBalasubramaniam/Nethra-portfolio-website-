<?php
require __DIR__ . '/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$locked = !empty($_SESSION['login_lock_until']) && time() < $_SESSION['login_lock_until'];
if ($locked) {
    $error = 'Too many failed attempts. Try again in ' . (int)ceil(($_SESSION['login_lock_until'] - time()) / 60) . ' minute(s).';
}

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');
    $cfg = admin_config();

    $ok = hash_equals($cfg['username'], $u) && password_verify($p, $cfg['password_hash']);

    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $u;
        unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);
        header('Location: index.php');
        exit;
    }

    $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
    if ($_SESSION['login_fails'] >= 5) {
        $_SESSION['login_lock_until'] = time() + 60;
        $_SESSION['login_fails'] = 0;
        $error = 'Too many failed attempts. Try again in 1 minute.';
        $locked = true;
    } else {
        $error = 'Incorrect username or password.';
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login — Nethra B.</title>
<style>
  html,body{margin:0;background:#08080a;color:#f4f2ef;font-family:"Instrument Sans",system-ui,-apple-system,sans-serif;height:100%}
  body{display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box}
  .card{width:100%;max-width:360px;background:#101014;border:1px solid rgba(244,242,239,0.1);border-radius:16px;padding:34px 30px}
  h1{margin:0 0 6px;font-size:1.4rem;font-weight:600;letter-spacing:-0.02em}
  p.sub{margin:0 0 26px;font-size:13px;color:rgba(244,242,239,0.5)}
  label{display:block;font-size:12px;letter-spacing:0.04em;color:rgba(244,242,239,0.6);margin:0 0 6px}
  input[type=text],input[type=password]{width:100%;box-sizing:border-box;background:#0c0c10;border:1px solid rgba(244,242,239,0.16);border-radius:8px;padding:11px 12px;color:#f4f2ef;font-size:14px;margin-bottom:16px}
  input:focus{outline:2px solid #ffd23f;outline-offset:1px;border-color:transparent}
  button{width:100%;background:#EF5152;color:#08080a;border:0;border-radius:999px;padding:12px;font-size:14px;font-weight:600;cursor:pointer}
  button:hover{background:#ffd23f}
  button:disabled{opacity:.5;cursor:not-allowed}
  .err{background:rgba(239,81,82,0.12);border:1px solid rgba(239,81,82,0.4);color:#ff8f8f;font-size:13px;border-radius:8px;padding:10px 12px;margin-bottom:18px}
</style>
</head>
<body>
  <div class="card">
    <h1>Admin</h1>
    <p class="sub">Sign in to manage case studies &amp; playground uploads.</p>
    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required <?= $locked ? 'disabled' : '' ?>>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required <?= $locked ? 'disabled' : '' ?>>
      <button type="submit" <?= $locked ? 'disabled' : '' ?>>Sign in</button>
    </form>
  </div>
</body>
</html>
