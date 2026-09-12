<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
require_login();

$csrf = csrf_token();

$pageTitle = 'Add case study';
$activeNav = 'case-add';
require __DIR__ . '/_chrome_top.php';
?>

<section class="block">
  <h2>Add a case study</h2>
  <p class="note">Shows in the work list and the homepage grid — headline, intro, meta, pillars, outcome metrics, and chapters are all editable here.</p>

  <div class="card-form add">
    <form method="post" action="api.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="create_case">
      <input type="hidden" name="redirect" value="cases.php">
      <label>Title</label>
      <input type="text" name="title" required>
      <div class="row2">
        <div><label>Tag / category</label><input type="text" name="tag" placeholder="e.g. FinTech Platform"></div>
        <div><label>Tags (comma separated)</label><input type="text" name="chips" placeholder="e.g. Mobile, Onboarding"></div>
      </div>
      <label>Summary</label>
      <textarea name="blurb" placeholder="One or two sentences shown on the card"></textarea>
      <div class="row2">
        <div>
          <label>Cover image</label>
          <p class="note" style="margin:-4px 0 6px;">Shown on the homepage grid and work list.</p>
          <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif">
        </div>
        <div>
          <label>Banner image (optional)</label>
          <p class="note" style="margin:-4px 0 6px;">Shown at the top of the case-detail page. Falls back to the cover image if left blank.</p>
          <input type="file" name="banner_image" accept="image/png,image/jpeg,image/webp,image/gif">
        </div>
      </div>

      <?php $c = []; $formId = 'new'; require __DIR__ . '/_case_form_fields.php'; require __DIR__ . '/_case_chapters_fields.php'; ?>

      <button class="submit" type="submit">Add case study</button>
    </form>
  </div>
</section>

<?php require __DIR__ . '/_chrome_bottom.php'; ?>
