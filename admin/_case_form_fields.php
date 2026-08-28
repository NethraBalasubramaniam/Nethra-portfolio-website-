<?php
// Shared rich-content fields for the case study add/edit forms. Include
// from inside an open <form>; expects $c (case data array — [] for the add
// form) and $formId (unique per-form string so repeated-row template ids
// don't collide when several edit forms sit on one page).
$c = $c ?? [];
$formId = $formId ?? 'new';
$meta = $c['meta'] ?? [];
$pillars = $c['pillars'] ?? [];
$metrics = $c['metrics'] ?? [];
?>
<label>Headline</label>
<input type="text" name="headline" value="<?= h($c['headline'] ?? '') ?>" placeholder="Big statement at the top of the case page — defaults to the title">

<label>Lede (intro paragraph)</label>
<textarea name="lede" placeholder="Defaults to the summary if left blank"><?= h($c['lede'] ?? '') ?></textarea>

<label class="section">Meta — Role / Client / Duration / Scope</label>
<div class="repeat-rows" id="meta-rows-<?= h($formId) ?>">
  <?php foreach ($meta as $m): ?>
    <div class="repeat-row">
      <input type="text" name="meta_k[]" value="<?= h($m['k'] ?? '') ?>" placeholder="Label, e.g. Role">
      <input type="text" name="meta_v[]" value="<?= h($m['v'] ?? '') ?>" placeholder="Value, e.g. Lead Product Designer">
      <button type="button" class="row-remove" onclick="removeRow(this)">×</button>
    </div>
  <?php endforeach; ?>
</div>
<template id="meta-tpl-<?= h($formId) ?>">
  <div class="repeat-row">
    <input type="text" name="meta_k[]" placeholder="Label, e.g. Role">
    <input type="text" name="meta_v[]" placeholder="Value, e.g. Lead Product Designer">
    <button type="button" class="row-remove" onclick="removeRow(this)">×</button>
  </div>
</template>
<button type="button" class="row-add" onclick="addRow('meta-rows-<?= h($formId) ?>', 'meta-tpl-<?= h($formId) ?>')">+ Add row</button>

<label class="section">Pillars — "What the platform does"</label>
<div class="repeat-rows" id="pillars-rows-<?= h($formId) ?>">
  <?php foreach ($pillars as $p): ?>
    <div class="repeat-row repeat-row-pillar">
      <input type="text" name="pillar_t[]" value="<?= h($p['t'] ?? '') ?>" placeholder="Title">
      <input type="text" name="pillar_b[]" value="<?= h($p['b'] ?? '') ?>" placeholder="Body">
      <div class="pillar-img-cell">
        <?php if (!empty($p['src'])): ?><img src="../<?= h($p['src']) ?>" alt="" class="pillar-thumb"><?php endif; ?>
        <input type="hidden" name="pillar_src[]" value="<?= h($p['src'] ?? '') ?>">
        <input type="file" name="pillar_image[]" accept="image/png,image/jpeg,image/webp,image/gif">
      </div>
      <button type="button" class="row-remove" onclick="removeRow(this)">×</button>
    </div>
  <?php endforeach; ?>
</div>
<template id="pillars-tpl-<?= h($formId) ?>">
  <div class="repeat-row repeat-row-pillar">
    <input type="text" name="pillar_t[]" placeholder="Title">
    <input type="text" name="pillar_b[]" placeholder="Body">
    <div class="pillar-img-cell">
      <input type="hidden" name="pillar_src[]" value="">
      <input type="file" name="pillar_image[]" accept="image/png,image/jpeg,image/webp,image/gif">
    </div>
    <button type="button" class="row-remove" onclick="removeRow(this)">×</button>
  </div>
</template>
<button type="button" class="row-add" onclick="addRow('pillars-rows-<?= h($formId) ?>', 'pillars-tpl-<?= h($formId) ?>')">+ Add pillar</button>
<p class="note" style="margin:-10px 0 18px;">Image is optional per pillar. Leave the whole section empty to hide it on the case page — numbering follows row order.</p>

<label class="section">Metrics — "Outcome"</label>
<div class="repeat-rows" id="metrics-rows-<?= h($formId) ?>">
  <?php foreach ($metrics as $mt): ?>
    <div class="repeat-row repeat-row-3">
      <input type="text" name="metric_n[]" value="<?= h($mt['n'] ?? '') ?>" placeholder="Number, e.g. 78">
      <input type="text" name="metric_suffix[]" value="<?= h($mt['suffix'] ?? '') ?>" placeholder="Suffix, e.g. %">
      <input type="text" name="metric_label[]" value="<?= h($mt['label'] ?? '') ?>" placeholder="Label">
      <button type="button" class="row-remove" onclick="removeRow(this)">×</button>
    </div>
  <?php endforeach; ?>
</div>
<template id="metrics-tpl-<?= h($formId) ?>">
  <div class="repeat-row repeat-row-3">
    <input type="text" name="metric_n[]" placeholder="Number, e.g. 78">
    <input type="text" name="metric_suffix[]" placeholder="Suffix, e.g. %">
    <input type="text" name="metric_label[]" placeholder="Label">
    <button type="button" class="row-remove" onclick="removeRow(this)">×</button>
  </div>
</template>
<button type="button" class="row-add" onclick="addRow('metrics-rows-<?= h($formId) ?>', 'metrics-tpl-<?= h($formId) ?>')">+ Add metric</button>
