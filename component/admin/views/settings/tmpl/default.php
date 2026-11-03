<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
$token = Factory::getApplication()->getFormToken();
$cfg   = $this->config;

$associateLinks = [
    'com'    => 'https://affiliate-program.amazon.com/assoc_credentials/home',
    'ca'     => 'https://associates.amazon.ca/assoc_credentials/home',
    'co.uk'  => 'https://affiliate-program.amazon.co.uk/assoc_credentials/home',
    'de'     => 'https://partnernet.amazon.de/assoc_credentials/home',
    'fr'     => 'https://partenaires.amazon.fr/assoc_credentials/home',
    'it'     => 'https://programma-affiliazione.amazon.it/assoc_credentials/home',
    'es'     => 'https://afiliados.amazon.es/assoc_credentials/home',
    'co.jp'  => 'https://affiliate-program.amazon.co.jp/assoc_credentials/home',
    'com.au' => 'https://affiliate-program.amazon.com.au/assoc_credentials/home',
    'in'     => 'https://affiliate-program.amazon.in/assoc_credentials/home',
    'com.br' => 'https://associados.amazon.com.br/assoc_credentials/home',
    'com.mx' => 'https://afiliados.amazon.com.mx/assoc_credentials/home',
    'nl'     => 'https://partnernet.amazon.nl/assoc_credentials/home',
    'pl'     => 'https://affiliate-program.amazon.pl/assoc_credentials/home',
    'se'     => 'https://affiliate-program.amazon.se/assoc_credentials/home',
    'ae'     => 'https://affiliate-program.amazon.ae/assoc_credentials/home',
    'sg'     => 'https://affiliate-program.amazon.sg/assoc_credentials/home',
];
$currentLink = $associateLinks[$cfg['api_country']] ?? $associateLinks['fr'];

// Detect if current key is OAuth format
$isOAuth = strncmp($cfg['api_key'] ?? '', 'amzn1.application-oa2-client.', 29) === 0;
?>
<style>
.aaj-tabs  { display:flex; gap:4px; border-bottom:2px solid var(--color-border-tertiary); margin-bottom:20px; }
.aaj-tab   { padding:8px 18px; cursor:pointer; border:1px solid transparent; border-bottom:none;
             border-radius:4px 4px 0 0; background:var(--color-background-secondary);
             color:var(--color-text-secondary); font-weight:500; font-size:14px; user-select:none; }
.aaj-tab:hover  { background:var(--color-background-tertiary); }
.aaj-tab.active { background:var(--color-background-primary); border-color:var(--color-border-secondary);
                  border-bottom-color:var(--color-background-primary); margin-bottom:-2px;
                  color:var(--color-text-info); }
.aaj-pane  { display:none; }
.aaj-pane.active { display:block; }
.aaj-info  { background:var(--color-background-info); border:1px solid var(--color-border-info);
             border-radius:8px; padding:14px 18px; margin-bottom:16px; font-size:13px;
             color:var(--color-text-primary); }
.aaj-info h6 { margin:0 0 8px; font-size:14px; font-weight:500; color:var(--color-text-info); }
.aaj-info ol { margin:4px 0 0 18px; padding:0; line-height:1.8; }
.aaj-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px;
             font-weight:500; vertical-align:middle; }
.aaj-badge-oauth   { background:var(--color-background-success); color:var(--color-text-success);
                     border:1px solid var(--color-border-success); }
.aaj-badge-classic { background:var(--color-background-secondary); color:var(--color-text-secondary);
                     border:1px solid var(--color-border-secondary); }
</style>

<form action="<?php echo Route::_('index.php?option=com_aaj&task=settings.save'); ?>"
      method="post" name="adminForm" id="adminForm">
<div style="max-width:920px;padding:20px 10px;">

  <?php if ($cfg['api_status']): ?>
  <div class="alert alert-success mb-3">
    ✅ <strong>API connected.</strong>
    Store: <strong>amazon.<?php echo htmlspecialchars($cfg['api_country']); ?></strong>
    &nbsp;|&nbsp; Tag: <strong><?php echo htmlspecialchars($cfg['api_tag']); ?></strong>
    &nbsp;|&nbsp; Mode: <span class="aaj-badge <?php echo $isOAuth ? 'aaj-badge-oauth' : 'aaj-badge-classic'; ?>">
      <?php echo $isOAuth ? 'OAuth 2.0 (Creators API)' : 'Classic AWS4'; ?>
    </span>
  </div>
  <?php endif; ?>

  <div class="aaj-tabs">
    <div class="aaj-tab active" data-tab="api">🔑 API Credentials</div>
    <div class="aaj-tab" data-tab="general">⚙️ General</div>
    <div class="aaj-tab" data-tab="output">🎨 Output</div>
  </div>

  <!-- ======================================================
       TAB API
  ====================================================== -->
  <div class="aaj-pane active" id="aaj-pane-api">

    <div class="aaj-info">
      <h6>📋 How to get your credentials — Amazon France & EU (Creators API)</h6>
      <ol>
        <li>Go to <a href="https://partenaires.amazon.fr" target="_blank" rel="noopener"><strong>partenaires.amazon.fr</strong></a> and log in to your Associates account</li>
        <li>Click <strong>Tools</strong> → <strong>Product Advertising API</strong> → you will be redirected to <strong>API des créateurs de contenu</strong></li>
        <li>Click <strong>"Create application"</strong> (or use your existing application)</li>
        <li>Copy the <strong>Client ID</strong> (format: <code>amzn1.application-oa2-client.xxx</code>) → paste in <em>Access Key / Client ID</em> below</li>
        <li>Generate and copy the <strong>Client Secret</strong> → paste in <em>Secret Key / Client Secret</em> below</li>
        <li>Your <strong>Associate Tag</strong> is your affiliate ID (e.g. <code>tdcon-21</code>) — visible in your Associates dashboard</li>
      </ol>
      <p style="margin:8px 0 0;font-size:12px;color:var(--color-text-secondary)">
        💡 AAJ auto-detects the key format: if your Access Key starts with <code>amzn1.application-oa2-client.</code> it uses OAuth 2.0 automatically. Otherwise it uses the classic AWS4 signature.
      </p>
    </div>

    <div class="card">
      <div class="card-body">

        <div class="row mb-3 align-items-center">
          <label class="col-md-3 col-form-label fw-bold">Amazon Store</label>
          <div class="col-md-5">
            <select name="jform[api_country]" class="form-select" id="aaj-country-select">
              <?php foreach ($this->stores as $code => $label): ?>
              <option value="<?php echo htmlspecialchars($code); ?>"<?php echo $cfg['api_country']===$code?' selected':''; ?>>
                <?php echo htmlspecialchars($label); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row mb-3 align-items-center">
          <label class="col-md-3 col-form-label fw-bold">
            Access Key
            <small class="d-block text-muted fw-normal" style="font-size:11px">or Client ID</small>
          </label>
          <div class="col-md-6">
            <input type="text" name="jform[api_key]" class="form-control font-monospace"
                   value="<?php echo htmlspecialchars($cfg['api_key']); ?>"
                   placeholder="AKIAIOSFODNN7EXAMPLE  or  amzn1.application-oa2-client.xxx"
                   autocomplete="off" style="font-size:12px;">
            <?php if ($isOAuth): ?>
            <small class="text-success">✅ OAuth 2.0 format detected (Creators API)</small>
            <?php else: ?>
            <small class="text-muted">Classic AWS4 key (AKIA...) or OAuth Client ID (amzn1.application-oa2-client...)</small>
            <?php endif; ?>
          </div>
        </div>

        <div class="row mb-3 align-items-center">
          <label class="col-md-3 col-form-label fw-bold">
            Secret Key
            <small class="d-block text-muted fw-normal" style="font-size:11px">or Client Secret</small>
          </label>
          <div class="col-md-6">
            <div class="input-group">
              <input type="password" name="jform[api_secret]" class="form-control font-monospace"
                     id="aaj-secret" value="<?php echo htmlspecialchars($cfg['api_secret']); ?>"
                     placeholder="Your secret key or client secret"
                     autocomplete="new-password" style="font-size:12px;">
              <button type="button" class="btn btn-outline-secondary" id="aaj-toggle-secret" title="Show/hide">👁</button>
            </div>
          </div>
        </div>

        <div class="row mb-4 align-items-center">
          <label class="col-md-3 col-form-label fw-bold">Associate Tag</label>
          <div class="col-md-4">
            <input type="text" name="jform[api_tag]" class="form-control"
                   value="<?php echo htmlspecialchars($cfg['api_tag']); ?>"
                   placeholder="yourname-21">
            <small class="text-muted">Your affiliate tracking ID (e.g. <code>tdcon-21</code>)</small>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 offset-md-3">
            <button type="button" class="btn btn-outline-primary px-4" id="aaj-test-api">
              🔌 Test Connection
            </button>
            <span id="aaj-test-result" class="ms-3 fw-bold"></span>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ======================================================
       TAB GENERAL
  ====================================================== -->
  <div class="aaj-pane" id="aaj-pane-general">
    <div class="card"><div class="card-body">

      <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label fw-bold">Shortcode Tag</label>
        <div class="col-md-4">
          <input type="text" name="jform[shortcode_tag]" class="form-control"
                 value="<?php echo htmlspecialchars($cfg['shortcode_tag']); ?>">
          <small class="text-muted">Used in articles: <code>{aaj type="box" asin="B0..."}</code>. Default: <code>aaj</code></small>
        </div>
      </div>

      <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label fw-bold">Cache Duration</label>
        <div class="col-md-3">
          <div class="input-group">
            <input type="number" name="jform[cache_duration]" class="form-control"
                   value="<?php echo (int)$cfg['cache_duration']; ?>" min="1" max="8760">
            <span class="input-group-text">hours</span>
          </div>
          <small class="text-muted">Default: 720 (30 days)</small>
        </div>
      </div>

      <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label fw-bold">Disclaimer</label>
        <div class="col-md-6">
          <textarea name="jform[disclaimer]" class="form-control" rows="2"><?php echo htmlspecialchars($cfg['disclaimer']); ?></textarea>
          <small class="text-muted">Optional affiliate disclosure text shown below product boxes</small>
        </div>
      </div>

    </div></div>
  </div>

  <!-- ======================================================
       TAB OUTPUT
  ====================================================== -->
  <div class="aaj-pane" id="aaj-pane-output">
    <div class="card"><div class="card-body">

      <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label fw-bold">Title Length</label>
        <div class="col-md-3">
          <div class="input-group">
            <input type="number" name="jform[title_length]" class="form-control"
                   value="<?php echo (int)$cfg['title_length']; ?>" min="0">
            <span class="input-group-text">chars</span>
          </div>
          <small class="text-muted">0 = no limit</small>
        </div>
      </div>

      <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label fw-bold">Button Style</label>
        <div class="col-md-4">
          <select name="jform[button_style]" class="form-select">
            <option value="amazon"<?php echo $cfg['button_style']==='amazon'?' selected':''; ?>>Amazon (orange gradient)</option>
            <option value="standard"<?php echo $cfg['button_style']==='standard'?' selected':''; ?>>Standard</option>
          </select>
        </div>
      </div>

      <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label fw-bold">Default Template</label>
        <div class="col-md-4">
          <select name="jform[box_template]" class="form-select">
            <option value="horizontal"<?php echo $cfg['box_template']==='horizontal'?' selected':''; ?>>Horizontal</option>
            <option value="vertical"<?php echo $cfg['box_template']==='vertical'?' selected':''; ?>>Vertical</option>
            <option value="list"<?php echo $cfg['box_template']==='list'?' selected':''; ?>>List</option>
          </select>
        </div>
      </div>

    </div></div>
  </div>

  <div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4">💾 Save Settings</button>
    <a href="<?php echo Route::_('index.php?option=com_aaj&view=dashboard'); ?>" class="btn btn-outline-secondary">← Dashboard</a>
  </div>

</div>
<input type="hidden" name="task" value="settings.save">
<?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>
</form>

<script>
(function () {
    document.querySelectorAll('.aaj-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.aaj-tab').forEach(function(t){ t.classList.remove('active'); });
            document.querySelectorAll('.aaj-pane').forEach(function(p){ p.classList.remove('active'); });
            this.classList.add('active');
            var pane = document.getElementById('aaj-pane-' + this.dataset.tab);
            if (pane) pane.classList.add('active');
        });
    });

    var secret = document.getElementById('aaj-secret');
    var toggle = document.getElementById('aaj-toggle-secret');
    if (toggle && secret) {
        toggle.addEventListener('click', function() {
            secret.type = secret.type === 'password' ? 'text' : 'password';
        });
    }

    var links = <?php echo json_encode($associateLinks); ?>;
    var sel   = document.getElementById('aaj-country-select');
    var link  = document.getElementById('aaj-assoc-link');
    if (sel) {
        sel.addEventListener('change', function() {
            if (link) link.href = links[this.value] || links['fr'];
        });
    }

    var testBtn = document.getElementById('aaj-test-api');
    var testRes = document.getElementById('aaj-test-result');
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            testBtn.disabled = true;
            testBtn.textContent = '⏳ Testing...';
            testRes.textContent = '';
            var f = document.adminForm;
            var body = new URLSearchParams({
                api_key:     f.querySelector('[name="jform[api_key]"]').value,
                api_secret:  f.querySelector('[name="jform[api_secret]"]').value,
                api_tag:     f.querySelector('[name="jform[api_tag]"]').value,
                api_country: f.querySelector('[name="jform[api_country]"]').value,
                '<?php echo $token; ?>': '1'
            });
            fetch('index.php?option=com_aaj&task=settings.testApi&format=json', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: body
            })
            .then(function(r){ return r.json(); })
            .then(function(d){
                testRes.innerHTML = d.success
                    ? '<span style="color:var(--color-text-success)">✅ ' + d.message + '</span>'
                    : '<span style="color:var(--color-text-danger)">❌ ' + d.message + '</span>';
            })
            .catch(function(e){
                testRes.innerHTML = '<span style="color:var(--color-text-danger)">❌ ' + e.message + '</span>';
            })
            .finally(function(){
                testBtn.disabled = false;
                testBtn.textContent = '🔌 Test Connection';
            });
        });
    }
})();
</script>
