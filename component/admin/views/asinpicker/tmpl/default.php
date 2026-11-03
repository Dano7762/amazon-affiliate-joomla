<?php defined('_JEXEC') or die; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Insérer un produit Amazon</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; color: #333; background: #fff; padding: 16px; }

/* Tabs */
.aaj-tabs { display: flex; border-bottom: 2px solid #e0e0e0; margin-bottom: 16px; }
.aaj-tab { padding: 8px 20px; cursor: pointer; border: none; background: none; font-size: 14px; color: #666; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all .2s; }
.aaj-tab.active { color: #e47911; border-bottom-color: #e47911; font-weight: 600; }
.aaj-tab:hover:not(.active) { color: #333; background: #f5f5f5; }
.aaj-pane { display: none; }
.aaj-pane.active { display: block; }

/* Search */
.aaj-search-row { display: flex; gap: 8px; margin-bottom: 12px; }
.aaj-search-row input { flex: 1; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
.aaj-search-row input:focus { outline: none; border-color: #e47911; box-shadow: 0 0 0 2px rgba(228,121,17,.15); }
.aaj-btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; transition: background .2s; white-space: nowrap; }
.aaj-btn-primary { background: #e47911; color: #fff; }
.aaj-btn-primary:hover { background: #c96b0e; }
.aaj-btn-primary:disabled { background: #ccc; cursor: not-allowed; }
.aaj-btn-secondary { background: #f0f0f0; color: #333; }
.aaj-btn-secondary:hover { background: #e0e0e0; }

/* Results */
.aaj-results { max-height: 320px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 4px; }
.aaj-result { display: flex; align-items: center; gap: 12px; padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background .15s; }
.aaj-result:last-child { border-bottom: none; }
.aaj-result:hover { background: #fdf5ec; }
.aaj-result.selected { background: #fdf0e0; outline: 2px solid #e47911; outline-offset: -2px; }
.aaj-result img { width: 50px; height: 50px; object-fit: contain; flex-shrink: 0; border: 1px solid #eee; border-radius: 3px; background: #fafafa; }
.aaj-result-info { flex: 1; min-width: 0; }
.aaj-result-title { font-size: 13px; font-weight: 500; line-height: 1.3; color: #111; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.aaj-result-meta { display: flex; gap: 12px; margin-top: 4px; font-size: 12px; color: #666; }
.aaj-result-price { color: #b12704; font-weight: 600; }
.aaj-result-asin { font-family: monospace; color: #888; }
.aaj-result-check { color: #e47911; font-size: 18px; flex-shrink: 0; opacity: 0; }
.aaj-result.selected .aaj-result-check { opacity: 1; }

/* Status messages */
.aaj-status { padding: 10px 12px; border-radius: 4px; font-size: 13px; text-align: center; }
.aaj-status-loading { background: #f0f4ff; color: #4a6cf7; }
.aaj-status-empty { background: #f9f9f9; color: #888; }
.aaj-status-error { background: #fff0f0; color: #c0392b; }
.aaj-status-warning { background: #fffbe6; color: #856404; border: 1px solid #ffe08a; }

/* Manual ASIN pane */
.aaj-field { margin-bottom: 14px; }
.aaj-label { display: block; font-weight: 500; margin-bottom: 5px; font-size: 13px; }
.aaj-input { width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
.aaj-input:focus { outline: none; border-color: #e47911; box-shadow: 0 0 0 2px rgba(228,121,17,.15); }
.aaj-row { display: flex; gap: 12px; }
.aaj-row .aaj-field { flex: 1; }
select.aaj-input { background: #fff; }

/* Preview */
.aaj-preview { background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; padding: 8px 12px; font-family: monospace; font-size: 13px; color: #333; min-height: 36px; word-break: break-all; }
.aaj-preview-label { font-size: 12px; color: #888; margin-bottom: 4px; }

/* Footer */
.aaj-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid #e8e8e8; margin-top: 14px; }
.aaj-footer-selected { font-size: 12px; color: #666; }
.aaj-footer-btns { display: flex; gap: 8px; }

/* Spinner */
.aaj-spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; margin-right: 6px; vertical-align: middle; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<div class="aaj-tabs">
    <button class="aaj-tab active" onclick="aajTab('search')">🔍 Rechercher</button>
    <button class="aaj-tab" onclick="aajTab('manual')">✏️ Saisie manuelle</button>
</div>

<!-- Onglet Recherche -->
<div id="aaj-pane-search" class="aaj-pane active">

    <?php if (!$this->apiConfigured): ?>
    <div class="aaj-status aaj-status-warning">
        ⚠️ Les clés API Amazon ne sont pas configurées dans <strong>Composants → AAJ → Settings</strong>.<br>
        La recherche sera disponible après configuration.
    </div>
    <?php else: ?>
    <div class="aaj-search-row">
        <input type="text" id="aaj-keywords" placeholder="Ex: casque bluetooth, aspirateur robot…" autofocus
               onkeydown="if(event.key==='Enter') aajSearch()">
        <button class="aaj-btn aaj-btn-primary" id="aaj-search-btn" onclick="aajSearch()">Chercher</button>
    </div>
    <div id="aaj-results-wrap">
        <div class="aaj-status aaj-status-empty">Entrez des mots-clés pour rechercher un produit Amazon.</div>
    </div>

    <div class="aaj-field" style="margin-top:12px;">
        <label class="aaj-label">Type de shortcode</label>
        <select class="aaj-input" id="aaj-type-search" onchange="aajUpdatePreviewSearch()">
            <option value="box">box — Cadre produit complet</option>
            <option value="link">link — Lien texte</option>
        </select>
    </div>
    <div class="aaj-preview-label">Aperçu du shortcode :</div>
    <div class="aaj-preview" id="aaj-preview-search">{aaj type="box" asin="…"}</div>
    <?php endif; ?>

</div>

<!-- Onglet Manuel -->
<div id="aaj-pane-manual" class="aaj-pane">
    <div class="aaj-row">
        <div class="aaj-field">
            <label class="aaj-label" for="aaj-asin">ASIN du produit Amazon</label>
            <input type="text" id="aaj-asin" class="aaj-input" placeholder="Ex: B07KB4YWQS"
                   maxlength="20" oninput="aajUpdatePreviewManual()">
        </div>
        <div class="aaj-field">
            <label class="aaj-label" for="aaj-type-manual">Type</label>
            <select id="aaj-type-manual" class="aaj-input" onchange="aajUpdatePreviewManual()">
                <option value="box">box — Cadre complet</option>
                <option value="link">link — Lien texte</option>
                <option value="field">field — Champ isolé</option>
                <option value="bestseller">bestseller — Liste</option>
                <option value="new_releases">new_releases — Nouveautés</option>
            </select>
        </div>
    </div>

    <div id="aaj-extra-field" style="display:none;" class="aaj-field">
        <label class="aaj-label" for="aaj-field-name">Champ (pour type=field)</label>
        <select id="aaj-field-name" class="aaj-input" onchange="aajUpdatePreviewManual()">
            <option value="price">price</option>
            <option value="title">title</option>
            <option value="url">url</option>
            <option value="rating">rating</option>
            <option value="reviews">reviews</option>
            <option value="prime">prime</option>
            <option value="image">image</option>
        </select>
    </div>

    <div class="aaj-field">
        <div class="aaj-preview-label">Aperçu du shortcode :</div>
        <div class="aaj-preview" id="aaj-preview-manual">{aaj type="box" asin=""}</div>
    </div>
</div>

<!-- Footer -->
<div class="aaj-footer">
    <span class="aaj-footer-selected" id="aaj-selected-info"></span>
    <div class="aaj-footer-btns">
        <button class="aaj-btn aaj-btn-secondary" onclick="window.parent.document.querySelector('.modal.show [data-bs-dismiss]')?.click()">Annuler</button>
        <button class="aaj-btn aaj-btn-primary" id="aaj-insert-btn" onclick="aajInsert()">Insérer dans l'article</button>
    </div>
</div>

<script>
var AAJ = {
    editor:     <?php echo json_encode($this->editorName); ?>,
    token:      <?php echo json_encode($this->token); ?>,
    root:       <?php echo json_encode(rtrim($this->root, '/')); ?>,
    selected:   null,  // {asin, title} from search
    activeTab:  'search'
};

/* ── Tabs ─────────────────────────────────────────────────────────── */
function aajTab(tab) {
    document.querySelectorAll('.aaj-tab').forEach(function(t, i) {
        t.classList.toggle('active', (i === (tab === 'search' ? 0 : 1)));
    });
    document.getElementById('aaj-pane-search').classList.toggle('active', tab === 'search');
    document.getElementById('aaj-pane-manual').classList.toggle('active', tab === 'manual');
    AAJ.activeTab = tab;
}

/* ── Search ───────────────────────────────────────────────────────── */
function aajSearch() {
    var kw = document.getElementById('aaj-keywords').value.trim();
    if (!kw) return;

    var wrap = document.getElementById('aaj-results-wrap');
    var btn  = document.getElementById('aaj-search-btn');
    wrap.innerHTML = '<div class="aaj-status aaj-status-loading"><span class="aaj-spinner"></span>Recherche en cours…</div>';
    btn.disabled   = true;
    AAJ.selected   = null;
    aajUpdatePreviewSearch();

    var url = AAJ.root + '/index.php?option=com_aaj&task=api.search'
            + '&keywords=' + encodeURIComponent(kw)
            + '&' + AAJ.token + '=1';

    fetch(url, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            if (!data.success || !data.data || data.data.length === 0) {
                var msg = data.message || 'Aucun résultat trouvé.';
                wrap.innerHTML = '<div class="aaj-status aaj-status-' + (data.api_unavailable ? 'warning' : 'empty') + '">' + msg + '</div>';
                return;
            }
            aajRenderResults(data.data, wrap);
        })
        .catch(function(e) {
            btn.disabled = false;
            wrap.innerHTML = '<div class="aaj-status aaj-status-error">Erreur de connexion. Réessayez.</div>';
        });
}

function aajRenderResults(items, wrap) {
    var html = '<div class="aaj-results">';
    items.forEach(function(item) {
        var img   = item.image ? '<img src="' + aajEsc(item.image) + '" alt="">' : '<img src="' + AAJ.root + '/media/com_aaj/img/placeholder-small.jpg" alt="">';
        var price = item.price ? '<span class="aaj-result-price">' + aajEsc(item.price) + '</span>' : '';
        html += '<div class="aaj-result" data-asin="' + aajEsc(item.asin) + '" data-title="' + aajEsc(item.title) + '" onclick="aajSelect(this)">'
              + img
              + '<div class="aaj-result-info">'
              + '<div class="aaj-result-title">' + aajEsc(item.title) + '</div>'
              + '<div class="aaj-result-meta">' + price + '<span class="aaj-result-asin">ASIN: ' + aajEsc(item.asin) + '</span></div>'
              + '</div>'
              + '<span class="aaj-result-check">✓</span>'
              + '</div>';
    });
    html += '</div>';
    wrap.innerHTML = html;
}

function aajSelect(el) {
    document.querySelectorAll('.aaj-result').forEach(function(r) { r.classList.remove('selected'); });
    el.classList.add('selected');
    AAJ.selected = { asin: el.dataset.asin, title: el.dataset.title };
    document.getElementById('aaj-selected-info').textContent = 'Sélectionné : ' + el.dataset.asin;
    aajUpdatePreviewSearch();
}

function aajUpdatePreviewSearch() {
    var asin = AAJ.selected ? AAJ.selected.asin : '…';
    var type = document.getElementById('aaj-type-search') ? document.getElementById('aaj-type-search').value : 'box';
    document.getElementById('aaj-preview-search').textContent = '{aaj type="' + type + '" asin="' + asin + '"}';
}

/* ── Manual ───────────────────────────────────────────────────────── */
function aajUpdatePreviewManual() {
    var asin  = (document.getElementById('aaj-asin').value || '').trim().toUpperCase();
    var type  = document.getElementById('aaj-type-manual').value;
    var extra = '';

    document.getElementById('aaj-extra-field').style.display = (type === 'field') ? 'block' : 'none';

    if (type === 'field') {
        extra = ' field="' + document.getElementById('aaj-field-name').value + '"';
    }
    if (type === 'bestseller' || type === 'new_releases') {
        extra = ' category="…"';
    }

    document.getElementById('aaj-preview-manual').textContent =
        '{aaj type="' + type + '" asin="' + (asin || '') + '"' + extra + '}';
}

/* ── Insert ───────────────────────────────────────────────────────── */
function aajInsert() {
    var shortcode = '';

    if (AAJ.activeTab === 'search') {
        if (!AAJ.selected) {
            alert('Veuillez sélectionner un produit dans les résultats ou utiliser l\'onglet "Saisie manuelle".');
            return;
        }
        shortcode = document.getElementById('aaj-preview-search').textContent;
    } else {
        var asin = document.getElementById('aaj-asin').value.trim().toUpperCase();
        if (!asin) { alert('Veuillez entrer un ASIN.'); return; }
        shortcode = document.getElementById('aaj-preview-manual').textContent;
    }

    // Insert into editor
    try {
        var instances = window.parent.Joomla.editors.instances;
        if (instances && instances[AAJ.editor]) {
            instances[AAJ.editor].replaceSelection(shortcode);
        } else {
            // Fallback: try to find textarea
            var ta = window.parent.document.getElementById(AAJ.editor);
            if (ta) {
                var start = ta.selectionStart;
                ta.value  = ta.value.substring(0, start) + shortcode + ta.value.substring(ta.selectionEnd);
                ta.selectionStart = ta.selectionEnd = start + shortcode.length;
            }
        }
    } catch(e) {
        // Cross-origin fallback
        window.parent.postMessage({ type: 'aaj_insert', shortcode: shortcode, editor: AAJ.editor }, '*');
    }

    // Close modal
    try {
        var closeBtn = window.parent.document.querySelector('.modal.show [data-bs-dismiss="modal"]');
        if (closeBtn) closeBtn.click();
        else window.parent.document.querySelector('.modal.show .btn-close')?.click();
    } catch(e) {}
}

function aajEsc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Init
aajUpdatePreviewManual();
</script>
</body>
</html>
