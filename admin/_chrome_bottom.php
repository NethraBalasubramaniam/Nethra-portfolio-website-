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
</script>
</main>
</body>
</html>
