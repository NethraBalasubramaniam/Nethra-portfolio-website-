<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
require_login();

$cases = db_list_cases();
$csrf = csrf_token();

$pageTitle = 'Slugs';
$activeNav = 'slugs';
require __DIR__ . '/_chrome_top.php';
?>

<section class="block">
  <h2>Slugs &amp; SEO</h2>
  <p class="note">The slug is the URL each case study lives at (e.g. <code>/your-slug-case-study</code>). Changing it moves the page — the old URL just stops working. Meta title/description are optional; when left blank the site falls back to auto-generated ones.</p>

  <?php if (!$cases): ?>
    <p class="empty">No case studies yet.</p>
  <?php else: ?>
    <table class="slug-table">
      <thead>
        <tr>
          <th>Case study</th>
          <th>Slug</th>
          <th>Meta title</th>
          <th>Meta description</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cases as $c): ?>
          <tr>
            <td class="title-cell">
              <h3><?= h($c['title'] ?? '') ?></h3>
              <span><?= h($c['tag'] ?? '') ?></span>
            </td>
            <td><code><?= h($c['slug'] ?? '') ?></code></td>
            <td class="<?= empty($c['metaTitle']) ? 'muted' : '' ?>"><?= h($c['metaTitle'] !== '' ? $c['metaTitle'] : '— auto') ?></td>
            <td class="<?= empty($c['metaDescription']) ? 'muted' : '' ?>"><?= h($c['metaDescription'] !== '' ? $c['metaDescription'] : '— auto') ?></td>
            <td>
              <button
                type="button"
                class="edit-btn"
                onclick='openSeoDialog(<?= json_encode([
                    'id' => $c['id'],
                    'title' => $c['title'] ?? '',
                    'slug' => $c['slug'] ?? '',
                    'metaTitle' => $c['metaTitle'] ?? '',
                    'metaDescription' => $c['metaDescription'] ?? '',
                  ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
              >Edit</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<dialog id="seoDialog" class="seo-dialog">
  <form method="post" action="api.php">
    <div class="dlg-head">
      <h3 id="seoDialogTitle">Edit slug &amp; SEO</h3>
      <button type="button" class="dlg-close" onclick="document.getElementById('seoDialog').close()" aria-label="Close">&times;</button>
    </div>
    <div class="dlg-body">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="update_case_seo">
      <input type="hidden" name="id" id="seoId">
      <input type="hidden" name="redirect" value="slugs.php">

      <label for="seoSlug">Slug</label>
      <input type="text" name="slug" id="seoSlug" required>

      <label for="seoMetaTitle">Meta title</label>
      <input type="text" name="meta_title" id="seoMetaTitle" maxlength="200" placeholder="Leave blank to auto-generate">

      <label for="seoMetaDescription">Meta description</label>
      <textarea name="meta_description" id="seoMetaDescription" maxlength="300" placeholder="Leave blank to auto-generate"></textarea>

      <button class="submit" type="submit">Save</button>
    </div>
  </form>
</dialog>

<script>
function openSeoDialog(c) {
  document.getElementById('seoDialogTitle').textContent = 'Edit slug & SEO — ' + c.title;
  document.getElementById('seoId').value = c.id;
  document.getElementById('seoSlug').value = c.slug;
  document.getElementById('seoMetaTitle').value = c.metaTitle;
  document.getElementById('seoMetaDescription').value = c.metaDescription;
  document.getElementById('seoDialog').showModal();
}
</script>

<?php require __DIR__ . '/_chrome_bottom.php'; ?>
