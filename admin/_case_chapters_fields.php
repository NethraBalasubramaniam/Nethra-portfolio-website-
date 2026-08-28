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
// Include from inside an open <form>; expects $c (case data array — [] for
// the add form) and $formId (unique per-form string).
$c = $c ?? [];
$formId = $formId ?? 'new';
$chapters = $c['chapters'] ?? [];
?>
<label class="section">Chapters</label>
<p class="note" style="margin-top:-4px;">Up to 4 images per chapter. Paragraphs: one per line. Editing an existing chapter that has secondary sub-headings (from the original write-up) will drop them — only the single sub-heading field here is kept.</p>
<div class="repeat-rows" id="chapters-rows-<?= h($formId) ?>">
  <?php foreach ($chapters as $ch): ?>
    <?php $chImages = chapter_images_for_display($ch); ?>
    <div class="chapter-block">
      <?php if (!empty($ch['sub2']) || !empty($ch['sub3'])): ?>
        <p class="chapter-warn">Has secondary sub-heading(s) from the original write-up (<?= h(trim(($ch['sub2'] ?? '') . ' ' . ($ch['sub3'] ?? ''))) ?>) not shown here — saving this section drops them.</p>
      <?php endif; ?>
      <div class="row2">
        <div><label>Title</label><input type="text" name="chapter_title[]" value="<?= h($ch['title'] ?? '') ?>"></div>
        <div><label>Sub-heading (optional)</label><input type="text" name="chapter_sub[]" value="<?= h($ch['sub'] ?? '') ?>"></div>
      </div>
      <label>Paragraphs (one per line)</label>
      <textarea name="chapter_paras[]" rows="4"><?= h(implode("\n", $ch['paras'] ?? [])) ?></textarea>
      <label>Pull quote (optional)</label>
      <input type="text" name="chapter_pull[]" value="<?= h($ch['pull'] ?? '') ?>">
      <label>Images (up to 4, optional)</label>
      <div class="chapter-img-grid">
        <?php for ($n = 1; $n <= 4; $n++): $img = $chImages[$n - 1] ?? null; ?>
          <div class="pillar-img-cell">
            <?php if ($img): ?><img src="../<?= h($img['src']) ?>" alt="" class="pillar-thumb"><?php endif; ?>
            <input type="hidden" name="chapter_image<?= $n ?>_src[]" value="<?= h($img['src'] ?? '') ?>">
            <input type="file" name="chapter_image<?= $n ?>[]" accept="image/png,image/jpeg,image/webp,image/gif">
          </div>
        <?php endfor; ?>
      </div>
      <button type="button" class="row-remove-block" onclick="removeRow(this, '.chapter-block')">Remove chapter</button>
    </div>
  <?php endforeach; ?>
</div>
<template id="chapters-tpl-<?= h($formId) ?>">
  <div class="chapter-block">
    <div class="row2">
      <div><label>Title</label><input type="text" name="chapter_title[]"></div>
      <div><label>Sub-heading (optional)</label><input type="text" name="chapter_sub[]"></div>
    </div>
    <label>Paragraphs (one per line)</label>
    <textarea name="chapter_paras[]" rows="4"></textarea>
    <label>Pull quote (optional)</label>
    <input type="text" name="chapter_pull[]">
    <label>Images (up to 4, optional)</label>
    <div class="chapter-img-grid">
      <div class="pillar-img-cell"><input type="hidden" name="chapter_image1_src[]" value=""><input type="file" name="chapter_image1[]" accept="image/png,image/jpeg,image/webp,image/gif"></div>
      <div class="pillar-img-cell"><input type="hidden" name="chapter_image2_src[]" value=""><input type="file" name="chapter_image2[]" accept="image/png,image/jpeg,image/webp,image/gif"></div>
      <div class="pillar-img-cell"><input type="hidden" name="chapter_image3_src[]" value=""><input type="file" name="chapter_image3[]" accept="image/png,image/jpeg,image/webp,image/gif"></div>
      <div class="pillar-img-cell"><input type="hidden" name="chapter_image4_src[]" value=""><input type="file" name="chapter_image4[]" accept="image/png,image/jpeg,image/webp,image/gif"></div>
    </div>
    <button type="button" class="row-remove-block" onclick="removeRow(this, '.chapter-block')">Remove chapter</button>
  </div>
</template>
<button type="button" class="row-add" onclick="addRow('chapters-rows-<?= h($formId) ?>', 'chapters-tpl-<?= h($formId) ?>')">+ Add chapter</button>
