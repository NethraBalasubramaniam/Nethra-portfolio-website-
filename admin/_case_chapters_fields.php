<?php
// Chapters section — deliberately kept OUT of _case_form_fields.php and
// saved through its own separate action (update_case_chapters), not the
// general update_case. Chapters are rebuilt whole from this form on every
// save (same repeatable-row pattern as pillars/meta/metrics), so if it
// shared a submit with title/tag/blurb/etc, fixing a typo anywhere on the
// case would silently rebuild — and for existing rich chapters, flatten —
// every chapter on save. A dedicated action means that only happens when
// someone deliberately opens and saves THIS section.
//
// Field order mirrors the case page: eyebrow, title, paragraphs, pull
// quote, sub-heading + images, then any number of extra "sub-heading +
// images" groups added with the "+ Add sub-heading & images" button. The
// first group posts as plain parallel arrays (chapter_sub[] /
// chapter_imageN[]); extra groups carry data-f attributes only and are
// given indexed names on submit by nameChapterGroups() in _chrome_bottom.php.
//
// Include from inside an open <form>; expects $c (case data array — [] for
// the add form) and $formId (unique per-form string).
$c = $c ?? [];
$formId = $formId ?? 'new';
$chapters = $c['chapters'] ?? [];

if (!function_exists('render_chapter_more_group')) {
    function render_chapter_more_group(int $num, string $sub = '', array $images = []): void { ?>
      <div class="chapter-group chapter-more-group">
        <label>Sub-heading <span class="group-num"><?= $num ?></span> (optional)</label>
        <input type="text" data-f="sub" value="<?= h($sub) ?>">
        <label>Images (up to 4, optional)</label>
        <div class="chapter-img-grid">
          <?php for ($n = 1; $n <= 4; $n++): $img = $images[$n - 1] ?? null; ?>
            <div class="pillar-img-cell">
              <?php if ($img): ?><img src="../<?= h($img['src']) ?>" alt="" class="pillar-thumb"><?php endif; ?>
              <input type="hidden" data-f="src" data-n="<?= $n ?>" value="<?= h($img['src'] ?? '') ?>">
              <input type="file" data-f="file" data-n="<?= $n ?>" accept="image/png,image/jpeg,image/webp,image/gif">
            </div>
          <?php endfor; ?>
        </div>
        <button type="button" class="row-remove-block" onclick="removeChapterGroup(this)">Remove this sub-heading</button>
      </div>
    <?php }
}
?>
<label class="section">Chapters</label>
<p class="note" style="margin-top:-4px;">Paragraphs: one per line. Up to 4 images per sub-heading — use "+ Add sub-heading &amp; images" for more sections inside a chapter.</p>
<div class="repeat-rows" id="chapters-rows-<?= h($formId) ?>">
  <?php foreach ($chapters as $ch): ?>
    <?php $groups = chapter_groups_for_display($ch); ?>
    <div class="chapter-block">
      <label>Eyebrow text (optional)</label>
      <input type="text" name="chapter_eyebrow[]" value="<?= h(chapter_eyebrow_for_display($ch)) ?>" placeholder="Short label shown above the title, e.g. Discovery &amp; Pivot — leave blank to show just the number">
      <label>Title</label>
      <input type="text" name="chapter_title[]" value="<?= h($ch['title'] ?? '') ?>">
      <label>Paragraphs (one per line)</label>
      <textarea name="chapter_paras[]" rows="4"><?= h(implode("\n", $ch['paras'] ?? [])) ?></textarea>
      <label>Pull quote (optional)</label>
      <input type="text" name="chapter_pull[]" value="<?= h($ch['pull'] ?? '') ?>">
      <div class="chapter-group">
        <label>Sub-heading (optional)</label>
        <input type="text" name="chapter_sub[]" value="<?= h($ch['sub'] ?? '') ?>">
        <label>Images (up to 4, optional)</label>
        <div class="chapter-img-grid">
          <?php for ($n = 1; $n <= 4; $n++): $img = $groups['images'][$n - 1] ?? null; ?>
            <div class="pillar-img-cell">
              <?php if ($img): ?><img src="../<?= h($img['src']) ?>" alt="" class="pillar-thumb"><?php endif; ?>
              <input type="hidden" name="chapter_image<?= $n ?>_src[]" value="<?= h($img['src'] ?? '') ?>">
              <input type="file" name="chapter_image<?= $n ?>[]" accept="image/png,image/jpeg,image/webp,image/gif">
            </div>
          <?php endfor; ?>
        </div>
      </div>
      <div class="chapter-more-rows">
        <?php foreach ($groups['more'] as $k => $grp) render_chapter_more_group($k + 2, $grp['sub'], $grp['images']); ?>
      </div>
      <button type="button" class="row-add" onclick="addChapterGroup(this, 'chapter-group-tpl-<?= h($formId) ?>')">+ Add sub-heading &amp; images</button>
      <button type="button" class="row-remove-block" onclick="removeRow(this, '.chapter-block')">Remove chapter</button>
    </div>
  <?php endforeach; ?>
</div>
<template id="chapters-tpl-<?= h($formId) ?>">
  <div class="chapter-block">
    <label>Eyebrow text (optional)</label>
    <input type="text" name="chapter_eyebrow[]" placeholder="Short label shown above the title, e.g. Discovery &amp; Pivot — leave blank to show just the number">
    <label>Title</label>
    <input type="text" name="chapter_title[]">
    <label>Paragraphs (one per line)</label>
    <textarea name="chapter_paras[]" rows="4"></textarea>
    <label>Pull quote (optional)</label>
    <input type="text" name="chapter_pull[]">
    <div class="chapter-group">
      <label>Sub-heading (optional)</label>
      <input type="text" name="chapter_sub[]">
      <label>Images (up to 4, optional)</label>
      <div class="chapter-img-grid">
        <?php for ($n = 1; $n <= 4; $n++): ?>
          <div class="pillar-img-cell"><input type="hidden" name="chapter_image<?= $n ?>_src[]" value=""><input type="file" name="chapter_image<?= $n ?>[]" accept="image/png,image/jpeg,image/webp,image/gif"></div>
        <?php endfor; ?>
      </div>
    </div>
    <div class="chapter-more-rows"></div>
    <button type="button" class="row-add" onclick="addChapterGroup(this, 'chapter-group-tpl-<?= h($formId) ?>')">+ Add sub-heading &amp; images</button>
    <button type="button" class="row-remove-block" onclick="removeRow(this, '.chapter-block')">Remove chapter</button>
  </div>
</template>
<template id="chapter-group-tpl-<?= h($formId) ?>">
  <?php render_chapter_more_group(2); ?>
</template>
<button type="button" class="row-add" onclick="addRow('chapters-rows-<?= h($formId) ?>', 'chapters-tpl-<?= h($formId) ?>')">+ Add chapter</button>
