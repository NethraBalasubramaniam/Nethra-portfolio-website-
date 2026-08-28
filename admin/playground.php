<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
require_login();

$playground = db_list_playground();
$csrf = csrf_token();

$pageTitle = 'Design playground';
$activeNav = 'playground';
require __DIR__ . '/_chrome_top.php';
?>

<section class="block">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:4px;flex-wrap:wrap;">
    <h2>Design playground</h2>
    <a href="playground-add.php" style="font-size:13px;border:1px solid rgba(244,242,239,0.2);border-radius:999px;padding:8px 16px;text-decoration:none;color:#f4f2ef;">+ Add playground piece</a>
  </div>
  <p class="note">Everything shown on the /playground page.</p>

  <?php if (!$playground): ?>
    <p class="empty">No playground pieces yet.</p>
  <?php endif; ?>

  <?php foreach ($playground as $it): ?>
    <div class="item-card">
      <details class="edit-wrap">
        <summary>
          <div class="item-row">
            <?php if (!empty($it['src'])): ?>
              <img src="../<?= h($it['src']) ?>" alt="">
            <?php else: ?>
              <img src="" alt="">
            <?php endif; ?>
            <div class="meta">
              <span class="tag"><?= h($it['tag'] ?? '') ?></span>
              <h3><?= h($it['title'] ?? '') ?></h3>
              <p><?= h($it['desc'] ?? '') ?></p>
            </div>
          </div>
        </summary>
        <div class="card-form">
          <form method="post" action="api.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="update_playground">
            <input type="hidden" name="id" value="<?= h($it['id']) ?>">
            <input type="hidden" name="redirect" value="playground.php">
            <label>Title</label>
            <input type="text" name="title" value="<?= h($it['title'] ?? '') ?>" required>
            <div class="row2">
              <div><label>Tag / category</label><input type="text" name="tag" value="<?= h($it['tag'] ?? '') ?>"></div>
              <div></div>
            </div>
            <label>Description</label>
            <textarea name="desc"><?= h($it['desc'] ?? '') ?></textarea>
            <div class="row2">
              <div><label>Likes</label><input type="text" name="likes" value="<?= h($it['likes'] ?? '0') ?>"></div>
              <div><label>Views</label><input type="text" name="views" value="<?= h($it['views'] ?? '0') ?>"></div>
            </div>
            <label>Image (leave blank to keep current)</label>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif">
            <button class="submit" type="submit">Save changes</button>
          </form>
        </div>
      </details>
      <span class="edit-pill" aria-hidden="true" style="pointer-events:none">Edit</span>
      <form class="delete" method="post" action="api.php" onsubmit="return confirm('Delete this playground piece?');">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="delete_playground">
        <input type="hidden" name="id" value="<?= h($it['id']) ?>">
        <input type="hidden" name="redirect" value="playground.php">
        <button type="submit">Delete</button>
      </form>
    </div>
  <?php endforeach; ?>
</section>

<?php require __DIR__ . '/_chrome_bottom.php'; ?>
