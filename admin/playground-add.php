<?php
require __DIR__ . '/auth.php';
require_login();

$csrf = csrf_token();

$pageTitle = 'Add playground piece';
$activeNav = 'playground-add';
require __DIR__ . '/_chrome_top.php';
?>

<section class="block">
  <h2>Add a playground piece</h2>
  <p class="note">Shows on the /playground page alongside everything else there.</p>

  <div class="card-form add">
    <form method="post" action="api.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="create_playground">
      <input type="hidden" name="redirect" value="playground.php">
      <label>Title</label>
      <input type="text" name="title" required>
      <div class="row2">
        <div><label>Tag / category</label><input type="text" name="tag" placeholder="e.g. Motion"></div>
        <div></div>
      </div>
      <label>Description</label>
      <textarea name="desc" placeholder="Short description shown in the lightbox"></textarea>
      <div class="row2">
        <div><label>Likes</label><input type="text" name="likes" value="0"></div>
        <div><label>Views</label><input type="text" name="views" value="0"></div>
      </div>
      <label>Image</label>
      <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif">
      <button class="submit" type="submit">Add playground piece</button>
    </form>
  </div>
</section>

<?php require __DIR__ . '/_chrome_bottom.php'; ?>
