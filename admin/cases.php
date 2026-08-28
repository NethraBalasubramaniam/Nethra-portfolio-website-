<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
require_login();

$cases = db_list_cases();
$csrf = csrf_token();

$pageTitle = 'Case studies';
$activeNav = 'cases';
require __DIR__ . '/_chrome_top.php';
?>

<section class="block">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:4px;flex-wrap:wrap;">
    <h2>Case studies</h2>
    <a href="case-add.php" style="font-size:13px;border:1px solid rgba(244,242,239,0.2);border-radius:999px;padding:8px 16px;text-decoration:none;color:#f4f2ef;">+ Add case study</a>
  </div>
  <p class="note">Title, tag, summary, tags, cover image, headline, intro, meta, pillars, and outcome metrics are all editable here. Chapter-by-chapter body copy still isn't — that stays as set directly in the database.</p>

  <?php if (!$cases): ?>
    <p class="empty">No case studies yet.</p>
  <?php endif; ?>

  <?php foreach ($cases as $c): ?>
    <div class="item-card">
      <details class="edit-wrap">
        <summary>
          <div class="item-row">
            <?php if (!empty($c['src'])): ?>
              <img src="../<?= h($c['src']) ?>" alt="">
            <?php else: ?>
              <img src="" alt="">
            <?php endif; ?>
            <div class="meta">
              <span class="tag"><?= h($c['tag'] ?? '') ?></span>
              <h3><?= h($c['title'] ?? '') ?></h3>
              <p><?= h($c['blurb'] ?? '') ?></p>
            </div>
          </div>
        </summary>
        <div class="card-form">
          <form method="post" action="api.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="update_case">
            <input type="hidden" name="id" value="<?= h($c['id']) ?>">
            <input type="hidden" name="redirect" value="cases.php">
            <label>Title</label>
            <input type="text" name="title" value="<?= h($c['title'] ?? '') ?>" required>
            <div class="row2">
              <div><label>Tag / category</label><input type="text" name="tag" value="<?= h($c['tag'] ?? '') ?>"></div>
              <div><label>Tags (comma separated)</label><input type="text" name="chips" value="<?= h(implode(', ', $c['chips'] ?? [])) ?>"></div>
            </div>
            <label>Summary</label>
            <textarea name="blurb"><?= h($c['blurb'] ?? '') ?></textarea>
            <label>Cover image (leave blank to keep current)</label>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif">

            <?php $formId = $c['id']; require __DIR__ . '/_case_form_fields.php'; ?>

            <button class="submit" type="submit">Save changes</button>
          </form>
        </div>
        <div class="card-form" style="border-top-color:rgba(244,242,239,0.18);">
          <form method="post" action="api.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="update_case_chapters">
            <input type="hidden" name="id" value="<?= h($c['id']) ?>">
            <input type="hidden" name="redirect" value="cases.php">

            <?php $formId = $c['id'] . '-ch'; require __DIR__ . '/_case_chapters_fields.php'; ?>

            <button class="submit" type="submit">Save chapters</button>
          </form>
        </div>
      </details>
      <span class="edit-pill" aria-hidden="true" style="pointer-events:none">Edit</span>
      <form class="delete" method="post" action="api.php" onsubmit="return confirm('Delete this case study? This cannot be undone.');">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="delete_case">
        <input type="hidden" name="id" value="<?= h($c['id']) ?>">
        <input type="hidden" name="redirect" value="cases.php">
        <button type="submit">Delete</button>
      </form>
    </div>
  <?php endforeach; ?>
</section>

<?php require __DIR__ . '/_chrome_bottom.php'; ?>
