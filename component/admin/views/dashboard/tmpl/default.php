<?php defined('_JEXEC') or die; ?>
<div class="container-fluid p-3">
  <div class="row mb-3">
    <div class="col">
      <h2>AAJ - Amazon Affiliates for Joomla</h2>
      <?php if (!$this->apiReady): ?>
        <div class="alert alert-warning">
          ⚠️ API not configured.
          <a href="<?php echo JRoute::_('index.php?option=com_aaj&view=settings'); ?>" class="btn btn-sm btn-warning ms-2">Configure API</a>
        </div>
      <?php else: ?>
        <div class="alert alert-success">✅ Amazon Creators API connected.</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-md-3">
      <div class="card text-center p-3 shadow-sm">
        <div class="fs-1 fw-bold text-primary"><?php echo $this->totalProducts; ?></div>
        <div>Cached Products</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center p-3 shadow-sm">
        <div class="fs-1 fw-bold text-info"><?php echo $this->totalLists; ?></div>
        <div>Cached Lists</div>
      </div>
    </div>
  </div>
  <div class="card mt-4">
    <div class="card-header"><strong>Shortcode Reference</strong></div>
    <div class="card-body">
      <p><code>{aaj type="box" asin="B0ABC12345"}</code> — Product box</p>
      <p><code>{aaj type="box" asin="B001,B002" template="vertical"}</code> — Multiple boxes</p>
      <p><code>{aaj type="link" asin="B0ABC12345" title="My product"}</code> — Text link</p>
      <p><code>{aaj type="bestseller" category="283155" items="5"}</code> — Bestsellers</p>
      <p><code>{aaj type="new_releases" category="283155" items="5"}</code> — New releases</p>
      <p><code>{aaj type="field" asin="B0ABC12345" field="price"}</code> — Single field</p>
    </div>
  </div>
</div>
