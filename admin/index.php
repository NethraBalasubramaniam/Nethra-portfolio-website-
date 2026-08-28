<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
require_login();

$caseCount = count(db_list_cases());
$pgCount = count(db_list_playground());

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/_chrome_top.php';
?>

<section class="block">
  <h2>Dashboard</h2>
  <p class="note">Manage what shows on the homepage grid, the work list, and the design playground.</p>

  <div class="dash-grid">
    <a class="dash-card" href="cases.php">
      <h3>View case studies</h3>
      <p><?= (int)$caseCount ?> case <?= $caseCount === 1 ? 'study' : 'studies' ?> — edit or delete</p>
    </a>
    <a class="dash-card" href="case-add.php">
      <h3>Add a case study</h3>
      <p>Title, tag, summary, tags, cover image</p>
    </a>
    <a class="dash-card" href="playground.php">
      <h3>View design playground</h3>
      <p><?= (int)$pgCount ?> piece<?= $pgCount === 1 ? '' : 's' ?> — edit or delete</p>
    </a>
    <a class="dash-card" href="playground-add.php">
      <h3>Add a playground piece</h3>
      <p>Title, tag, description, image</p>
    </a>
  </div>
</section>

<?php require __DIR__ . '/_chrome_bottom.php'; ?>
