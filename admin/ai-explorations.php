<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
require_login();

$items = db_list_ai_explorations();
$csrf = csrf_token();

$pageTitle = 'AI Explorations';
$activeNav = 'ai-explorations';
require __DIR__ . '/_chrome_top.php';
?>

<section class="block">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:4px;flex-wrap:wrap;">
    <h2>AI Explorations</h2>
    <a href="ai-explorations-add.php" style="font-size:13px;border:1px solid rgba(244,242,239,0.2);border-radius:999px;padding:8px 16px;text-decoration:none;color:#f4f2ef;">+ Add video</a>
  </div>
  <p class="note">Everything shown on the /ai-explorations page. Cover image is pulled from YouTube automatically unless you upload your own.</p>

  <?php if (!$items): ?>
    <p class="empty">No videos yet.</p>
  <?php endif; ?>

  <?php foreach ($items as $it): ?>
    <div class="item-card">
      <details class="edit-wrap">
        <summary>
          <div class="item-row">
            <?php if (!empty($it['src'])): ?>
              <img src="<?= h(admin_img_src($it['src'])) ?>" alt="">
            <?php else: ?>
              <img src="" alt="">
            <?php endif; ?>
            <div class="meta">
              <span class="tag">YouTube</span>
              <h3><?= h($it['title'] ?? '') ?></h3>
              <p><?= h($it['youtubeUrl'] ?? '') ?></p>
            </div>
          </div>
        </summary>
        <div class="card-form">
          <form method="post" action="api.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="update_ai_exploration">
            <input type="hidden" name="id" value="<?= h($it['id']) ?>">
            <input type="hidden" name="redirect" value="ai-explorations.php">
            <label>Title</label>
            <input type="text" name="title" value="<?= h($it['title'] ?? '') ?>" required>
            <label>YouTube link</label>
            <input type="text" name="youtube_url" value="<?= h($it['youtubeUrl'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..." required>
            <label>Cover image (leave blank to keep current)</label>
            <?php if (!empty($it['src'])): ?><img src="<?= h(admin_img_src($it['src'])) ?>" alt="" class="pillar-thumb" style="height:60px;margin-bottom:8px;"><?php endif; ?>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif">
            <label style="display:flex;align-items:center;gap:8px;margin-top:2px;">
              <input type="checkbox" name="use_youtube_thumb" value="1" style="width:auto;margin:0;">
              <span style="font-size:12.5px;color:rgba(244,242,239,0.6);">Re-fetch cover from YouTube instead (overrides any upload above)</span>
            </label>
            <button class="submit" type="submit">Save changes</button>
          </form>
        </div>
      </details>
      <span class="edit-pill" aria-hidden="true" style="pointer-events:none">Edit</span>
      <form class="delete" method="post" action="api.php" onsubmit="return confirm('Delete this video?');">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="delete_ai_exploration">
        <input type="hidden" name="id" value="<?= h($it['id']) ?>">
        <input type="hidden" name="redirect" value="ai-explorations.php">
        <button type="submit">Delete</button>
      </form>
    </div>
  <?php endforeach; ?>
</section>

<?php require __DIR__ . '/_chrome_bottom.php'; ?>
