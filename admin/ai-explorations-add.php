<?php
require __DIR__ . '/auth.php';
require_login();

$csrf = csrf_token();

$pageTitle = 'Add AI exploration';
$activeNav = 'ai-explorations-add';
require __DIR__ . '/_chrome_top.php';
?>

<section class="block">
  <h2>Add a video</h2>
  <p class="note">Shows on the /ai-explorations page. Paste any YouTube link (watch, youtu.be, or shorts) — the cover image is pulled from YouTube automatically unless you upload your own below.</p>

  <div class="card-form add">
    <form method="post" action="api.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="create_ai_exploration">
      <input type="hidden" name="redirect" value="ai-explorations.php">
      <label>Title</label>
      <input type="text" name="title" required>
      <label>YouTube link</label>
      <input type="text" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required>
      <label>Cover image (optional — leave blank to auto-fetch from YouTube)</label>
      <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif">
      <button class="submit" type="submit">Add video</button>
    </form>
  </div>
</section>

<?php require __DIR__ . '/_chrome_bottom.php'; ?>
