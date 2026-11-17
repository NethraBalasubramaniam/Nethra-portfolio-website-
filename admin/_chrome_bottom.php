<script>
function addRow(containerId, tplId) {
  var tpl = document.getElementById(tplId);
  var container = document.getElementById(containerId);
  if (tpl && container) container.appendChild(tpl.content.cloneNode(true));
}
function removeRow(btn, selector) {
  var row = btn.closest(selector || '.repeat-row');
  if (row) row.remove();
}

// Extra "sub-heading + images" groups inside a chapter (see
// _case_chapters_fields.php). Numbering starts at 2 — the chapter's first
// sub-heading is group 1.
function renumberChapterGroups(block) {
  block.querySelectorAll('.chapter-more-group .group-num').forEach(function (el, k) { el.textContent = k + 2; });
}
function addChapterGroup(btn, tplId) {
  var block = btn.closest('.chapter-block');
  var tpl = document.getElementById(tplId);
  if (!block || !tpl) return;
  block.querySelector('.chapter-more-rows').appendChild(tpl.content.cloneNode(true));
  renumberChapterGroups(block);
}
function removeChapterGroup(btn) {
  var block = btn.closest('.chapter-block');
  btn.closest('.chapter-more-group').remove();
  if (block) renumberChapterGroups(block);
}
// Chapter rows are plain parallel arrays (chapter_title[] etc.), but extra
// groups are a variable-length list per chapter, so they need explicit
// indices: chapter i (DOM order = its position in chapter_title[]) and
// group j. Named right before submit so add/remove never needs bookkeeping.
document.addEventListener('submit', function (e) {
  e.target.querySelectorAll('.chapter-block').forEach(function (block, i) {
    block.querySelectorAll('.chapter-more-group').forEach(function (grp, j) {
      grp.querySelectorAll('[data-f]').forEach(function (el) {
        var f = el.getAttribute('data-f'), n = el.getAttribute('data-n');
        if (f === 'sub') el.name = 'chapter_more[' + i + '][' + j + '][sub]';
        else if (f === 'src') el.name = 'chapter_more[' + i + '][' + j + '][src][' + n + ']';
        else if (f === 'file') el.name = 'chapter_more_img_' + i + '_' + j + '_' + n;
      });
    });
  });
}, true);
</script>
</main>
</body>
</html>
