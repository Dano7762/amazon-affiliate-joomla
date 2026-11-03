<?php defined('_JEXEC') or die; ?>
<form action="<?php echo JRoute::_('index.php?option=com_aaj'); ?>" method="post" name="adminForm">
<div class="container-fluid p-3">
  <div class="d-flex justify-content-between mb-3">
    <div class="d-flex gap-2">
      <input type="text" id="aaj-asin" class="form-control form-control-sm" style="width:180px" placeholder="Enter ASIN...">
      <button type="button" class="btn btn-sm btn-primary" id="aaj-fetch">Fetch Product</button>
    </div>
    <small class="text-muted align-self-center"><?php echo $this->total; ?> products cached</small>
  </div>
  <div id="aaj-msg" class="mb-2" style="display:none"></div>
  <table class="table table-striped table-sm">
    <thead><tr>
      <th width="20"><input type="checkbox" onclick="Joomla.checkAll(this)"></th>
      <th width="60">Image</th><th>Title</th><th width="120">ASIN</th>
      <th width="90">Price</th><th width="80">Rating</th><th width="140">Updated</th>
    </tr></thead>
    <tbody>
    <?php if (empty($this->products)): ?>
      <tr><td colspan="7" class="text-center text-muted py-3">No products cached yet.</td></tr>
    <?php else: foreach ($this->products as $p):
        $imgs = json_decode($p['image_ids'] ?? '{}', true); ?>
      <tr>
        <td><input type="checkbox" name="cid[]" value="<?php echo (int)$p['id']; ?>" onclick="Joomla.isChecked(this.checked)"></td>
        <td><?php if (!empty($imgs['small'])): ?><img src="<?php echo htmlspecialchars($imgs['small']); ?>" height="45"><?php endif; ?></td>
        <td><strong><?php echo htmlspecialchars($p['title'] ?? ''); ?></strong></td>
        <td><code><?php echo htmlspecialchars($p['asin']); ?></code></td>
        <td><?php echo htmlspecialchars(($p['currency']??'').' '.($p['price']??'')); ?></td>
        <td><?php echo $p['rating'] ? '⭐ '.number_format((float)$p['rating'],1) : '—'; ?></td>
        <td><small><?php echo htmlspecialchars($p['date_updated']??''); ?></small></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<input type="hidden" name="task" value="">
<input type="hidden" name="boxchecked" value="0">
<?php echo JHtml::_('form.token'); ?>
</form>
<script>
document.getElementById('aaj-fetch')?.addEventListener('click', function() {
  var asin = document.getElementById('aaj-asin').value.trim();
  if (!asin) return;
  var msg = document.getElementById('aaj-msg');
  msg.style.display='block'; msg.className='alert alert-info'; msg.textContent='Loading...';
  fetch('index.php?option=com_aaj&task=settings.fetchAsin&format=json&<?php echo JSession::getFormToken(); ?>=1&asin='+encodeURIComponent(asin))
    .then(r=>r.json()).then(d=>{
      msg.className = d.success ? 'alert alert-success' : 'alert alert-danger';
      msg.textContent = d.success ? '✅ '+d.title : '❌ '+d.message;
      if (d.success) setTimeout(()=>location.reload(), 1000);
    });
});
</script>
