<?php
// Shared header/nav + styles for every admin page. Set $pageTitle and
// $activeNav before including this, e.g.:
//   $pageTitle = 'Case studies'; $activeNav = 'cases';
//   require __DIR__ . '/_chrome_top.php';
// Not meant to be requested directly — only makes sense after auth.php has
// run require_login(), which is what defines ADMIN_ROOT.
if (!defined('ADMIN_ROOT') || !is_logged_in()) {
    header('Location: login.php');
    exit;
}
$navItems = [
    'cases' => ['cases.php', 'Case studies'],
    'case-add' => ['case-add.php', 'Add case study'],
    'playground' => ['playground.php', 'Playground'],
    'playground-add' => ['playground-add.php', 'Add playground piece'],
];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'Admin') ?> — Nethra B.</title>
<style>
  *{box-sizing:border-box}
  html,body{margin:0;background:#08080a;color:#f4f2ef;font-family:"Instrument Sans",system-ui,-apple-system,sans-serif}
  a{color:#EF5152}
  header{position:sticky;top:0;z-index:10;background:#0c0c10;border-bottom:1px solid rgba(244,242,239,0.1)}
  .header-top{display:flex;align-items:center;justify-content:space-between;padding:16px 28px}
  header h1{font-size:16px;margin:0;font-weight:600}
  header a.logout{font-size:13px;color:rgba(244,242,239,0.6);text-decoration:none;border:1px solid rgba(244,242,239,0.18);border-radius:999px;padding:7px 16px}
  header a.logout:hover{color:#f4f2ef;border-color:#f4f2ef}
  nav.admin-nav{display:flex;gap:4px;padding:0 24px 12px;overflow-x:auto}
  nav.admin-nav a{flex:none;text-decoration:none;font-size:13px;color:rgba(244,242,239,0.55);padding:7px 14px;border-radius:999px;white-space:nowrap}
  nav.admin-nav a:hover{color:#f4f2ef}
  nav.admin-nav a.active{color:#08080a;background:#f4f2ef;font-weight:600}
  main{max-width:1000px;margin:0 auto;padding:32px 24px 100px}
  section.block{margin-bottom:56px}
  h2{font-size:1.4rem;margin:0 0 4px;font-weight:600;letter-spacing:-0.02em}
  p.note{margin:0 0 22px;font-size:13px;color:rgba(244,242,239,0.5)}

  .item-card{position:relative;border:1px solid rgba(244,242,239,0.1);border-radius:12px;background:#101014;margin-bottom:12px;overflow:hidden}
  details.edit-wrap summary{list-style:none;cursor:pointer}
  details.edit-wrap summary::-webkit-details-marker{display:none}
  .item-row{display:flex;gap:16px;padding:16px;padding-right:96px}
  .item-row img{width:96px;height:72px;object-fit:cover;border-radius:8px;background:#1a1a20;flex:none}
  .item-row .meta{flex:1;min-width:0}
  .item-row .meta h3{margin:0 0 4px;font-size:15px;font-weight:600}
  .item-row .meta .tag{font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:rgba(244,242,239,0.45)}
  .item-row .meta p{margin:6px 0 0;font-size:13px;color:rgba(244,242,239,0.6);line-height:1.4}
  .edit-pill{position:absolute;top:16px;right:16px;font-size:12.5px;color:#f4f2ef;border:1px solid rgba(244,242,239,0.2);border-radius:999px;padding:6px 14px;white-space:nowrap}
  details.edit-wrap:hover ~ .edit-pill,details.edit-wrap[open] ~ .edit-pill{border-color:#ffd23f;color:#ffd23f}
  form.delete{position:absolute;top:52px;right:16px}
  form.delete button{background:none;border:1px solid rgba(239,81,82,0.4);color:#ff8f8f;border-radius:999px;padding:6px 14px;font-size:12.5px;cursor:pointer}
  form.delete button:hover{background:rgba(239,81,82,0.14)}

  .card-form{border-top:1px solid rgba(244,242,239,0.1);padding:20px;background:#0c0c10}
  .card-form.add{border-top:0;border:1px solid rgba(244,242,239,0.12);border-radius:12px;max-width:720px}
  label{display:block;font-size:12px;letter-spacing:0.03em;color:rgba(244,242,239,0.55);margin:0 0 6px}
  label.section{margin-top:20px;font-size:13px;color:#f4f2ef;font-weight:600}
  input[type=text],input[type=number],textarea,input[type=file]{width:100%;background:#0c0c10;border:1px solid rgba(244,242,239,0.16);border-radius:8px;padding:9px 11px;color:#f4f2ef;font-size:13.5px;font-family:inherit;margin-bottom:14px}
  textarea{resize:vertical;min-height:64px}
  input:focus,textarea:focus{outline:2px solid #ffd23f;outline-offset:1px;border-color:transparent}
  .row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}

  .repeat-rows{margin-bottom:6px}
  .repeat-row{display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center}
  .repeat-row-wide{grid-template-columns:1fr 2fr auto}
  .repeat-row-3{grid-template-columns:0.55fr 0.55fr 2fr auto}
  .repeat-row-pillar{grid-template-columns:1fr 1.6fr 150px auto;align-items:start}
  .repeat-row input{margin-bottom:0}
  .pillar-img-cell{display:flex;flex-direction:column;gap:4px}
  .pillar-img-cell input[type=file]{margin-bottom:0;padding:5px 6px;font-size:11px}
  .pillar-thumb{width:100%;height:26px;object-fit:cover;border-radius:4px;background:#1a1a20}
  .row-remove{background:none;border:1px solid rgba(239,81,82,0.4);color:#ff8f8f;border-radius:8px;width:34px;height:34px;cursor:pointer;font-size:16px;line-height:1;flex:none}
  .row-remove:hover{background:rgba(239,81,82,0.14)}
  .row-add{display:inline-block;background:none;border:1px dashed rgba(244,242,239,0.3);color:rgba(244,242,239,0.7);border-radius:8px;padding:8px 14px;font-size:12.5px;cursor:pointer;margin:0 0 18px}
  .row-add:hover{border-color:#ffd23f;color:#ffd23f}

  .chapter-block{border:1px solid rgba(244,242,239,0.12);border-radius:10px;padding:16px;margin-bottom:14px;background:#0c0c10}
  .chapter-img-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px}
  .row-remove-block{background:none;border:1px solid rgba(239,81,82,0.4);color:#ff8f8f;border-radius:999px;padding:6px 14px;font-size:12px;cursor:pointer}
  .row-remove-block:hover{background:rgba(239,81,82,0.14)}
  .chapter-warn{margin:0 0 14px;font-size:12.5px;color:#ffd23f;background:rgba(255,210,63,0.1);border:1px solid rgba(255,210,63,0.3);border-radius:8px;padding:8px 12px}
  button.submit{background:#EF5152;color:#08080a;border:0;border-radius:999px;padding:10px 22px;font-size:13.5px;font-weight:600;cursor:pointer}
  button.submit:hover{background:#ffd23f}
  .empty{font-size:13px;color:rgba(244,242,239,0.4);padding:14px 0}

  .dash-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
  .dash-card{display:block;text-decoration:none;border:1px solid rgba(244,242,239,0.1);border-radius:14px;background:#101014;padding:22px;transition:border-color .2s ease}
  .dash-card:hover{border-color:rgba(244,242,239,0.3)}
  .dash-card h3{margin:0 0 6px;font-size:16px;color:#f4f2ef;font-weight:600}
  .dash-card p{margin:0;font-size:13px;color:rgba(244,242,239,0.5)}
</style>
</head>
<body>
<header>
  <div class="header-top">
    <h1>Admin — Nethra B.</h1>
    <a class="logout" href="logout.php">Log out</a>
  </div>
  <nav class="admin-nav">
    <a href="index.php"<?= ($activeNav ?? '') === 'dashboard' ? ' class="active"' : '' ?>>Dashboard</a>
    <?php foreach ($navItems as $key => [$href, $label]): ?>
      <a href="<?= h($href) ?>"<?= ($activeNav ?? '') === $key ? ' class="active"' : '' ?>><?= h($label) ?></a>
    <?php endforeach; ?>
  </nav>
</header>
<main>
