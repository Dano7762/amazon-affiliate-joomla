<?php defined('_JEXEC') or die; ?>
<form action="<?php echo JRoute::_('index.php?option=com_aaj'); ?>" method="post" name="adminForm">
<div class="container-fluid p-3">
  <table class="table table-striped table-sm">
    <thead><tr>
      <th width="20"><input type="checkbox" onclick="Joomla.checkAll(this)"></th>
      <th width="100">Type</th><th>Cache Key</th>
      <th width="100">Category</th><th width="80">Country</th>
      <th width="60">Items</th><th width="140">Updated</th>
    </tr></thead>
    <tbody>
    <?php if (empty($this->lists)): ?>
      <tr><td colspan="7" class="text-center text-muted py-3">No lists cached yet.</td></tr>
    <?php else: foreach ($this->lists as $l):
        $prods = json_decode($l['products']??'[]',true); ?>
      <tr>
        <td><input type="checkbox" name="cid[]" value="<?php echo (int)$l['id']; ?>" onclick="Joomla.isChecked(this.checked)"></td>
        <td><span class="badge bg-<?php echo $l['type']==='bestseller'?'warning':'info'; ?>"><?php echo htmlspecialchars($l['type']); ?></span></td>
        <td><small class="text-muted font-monospace"><?php echo htmlspecialchars(substr($l['list_key']??'',0,40)); ?></small></td>
        <td><?php echo htmlspecialchars($l['category']??'—'); ?></td>
        <td><?php echo htmlspecialchars($l['country']??'—'); ?></td>
        <td class="text-center"><?php echo count($prods); ?></td>
        <td><small><?php echo htmlspecialchars($l['date_updated']??''); ?></small></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<input type="hidden" name="task" value="">
<input type="hidden" name="boxchecked" value="0">
<?php echo JHtml::_('form.token'); ?>
</form>
