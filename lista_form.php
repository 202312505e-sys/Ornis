<?php
// lista_form.php — Crear / Editar lista de avistamiento con múltiples especies
declare(strict_types=1);
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/rutas.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';
Auth::requerir('auth.php');

$id_usuario  = Auth::id();
$nombre_user = Auth::nombre();

function e(mixed $v):string{ return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8'); }

// EDIT MODE
$edit_id = (int)($_GET['edit'] ?? 0);
$editando = false;
$lista = null;
$especies_lista = [];

if ($edit_id > 0) {
    $st = $pdo->prepare("SELECT * FROM listas_avistamiento WHERE id_lista=? AND id_usuario=?");
    $st->execute([$edit_id, $id_usuario]);
    $lista = $st->fetch();
    if ($lista) {
        $editando = true;
        $st2 = $pdo->prepare("SELECT le.*, t.sci_name, t.family FROM lista_especies le LEFT JOIN especies_taxonomia t ON le.species_code=t.species_code WHERE le.id_lista=? ORDER BY le.orden ASC");
        $st2->execute([$edit_id]);
        $especies_lista = $st2->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= $editando ? 'Editar' : 'Nueva' ?> Lista — ORNIS</title>
  <link rel="stylesheet" href="<?= CSS_URL ?>"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script>(function(){const t=localStorage.getItem('ornis-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
  <style>
    .page-wrap{max-width:900px;margin:0 auto;padding:calc(var(--nav-h)+28px) 20px 100px}

    /* Top action bar */
    .topbar{
      position:fixed;top:var(--nav-h);left:0;right:0;z-index:90;
      background:var(--mo-corona);color:#fff;
      display:flex;align-items:center;justify-content:space-between;
      padding:10px 20px;
    }
    .topbar-left{display:flex;align-items:center;gap:12px}
    .topbar-title{font-weight:700;font-size:.95rem}
    .topbar-sub{font-size:.78rem;opacity:.8}
    .btn-back-top{background:transparent;color:#fff;border:none;font-size:1.1rem;cursor:pointer;padding:4px 8px}
    .btn-save-top{background:#fff;color:var(--mo-corona);border:none;padding:8px 20px;border-radius:var(--r-pill);font-weight:700;font-size:.88rem;cursor:pointer;font-family:var(--font-body)}
    .btn-save-top:hover{background:#e8f5ff}

    /* Sections */
    .form-section{background:var(--bg-card);border:1px solid var(--border-base);border-radius:var(--r-lg);margin-bottom:16px;overflow:hidden}
    .form-section-head{padding:14px 20px;border-bottom:1px solid var(--border-base);display:flex;align-items:center;gap:10px}
    .form-section-head h3{font-size:.92rem;font-weight:700;color:var(--mo-corona)}
    .form-section-head i{color:var(--mo-corona);width:18px}
    .form-section-body{padding:20px}

    /* Inputs */
    .fgroup{margin-bottom:16px}
    .flabel{display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:6px;letter-spacing:.3px}
    .flabel .req{color:#e05050;margin-left:2px}
    .finput{
      width:100%;padding:11px 14px;
      background:var(--bg-input);border:1.5px solid var(--border-base);
      border-radius:var(--r-md);color:var(--text-primary);
      font-size:.92rem;font-family:var(--font-body);outline:none;
      transition:border-color var(--t),box-shadow var(--t);
    }
    .finput:focus{border-color:var(--mo-corona);box-shadow:0 0 0 3px rgba(26,143,175,.15)}
    .finput::placeholder{color:var(--text-muted)}
    .frow{display:flex;gap:12px}
    .frow .fgroup{flex:1}

    /* Protocolo chips */
    .protocolo-chips{display:flex;gap:8px;flex-wrap:wrap}
    .proto-chip{
      padding:8px 16px;border-radius:var(--r-pill);border:1.5px solid var(--border-base);
      background:var(--bg-input);color:var(--text-secondary);
      cursor:pointer;font-size:.83rem;font-family:var(--font-body);
      transition:all var(--t);display:flex;align-items:center;gap:5px;
    }
    .proto-chip:hover{border-color:var(--mo-corona);color:var(--mo-corona)}
    .proto-chip.selected{background:var(--mo-corona);color:#fff;border-color:var(--mo-corona)}

    /* ── Buscador de especie (eBird style) */
    .sp-search-wrap{position:relative;margin-bottom:16px}
    .sp-search-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--mo-corona);font-size:.9rem}
    .sp-search-input{
      width:100%;padding:12px 14px 12px 40px;
      background:var(--bg-input);border:1.5px solid var(--border-base);
      border-radius:var(--r-md);color:var(--text-primary);
      font-size:.92rem;font-family:var(--font-body);outline:none;
      transition:border-color var(--t);
    }
    .sp-search-input:focus{border-color:var(--mo-corona);box-shadow:0 0 0 3px rgba(26,143,175,.15)}
    .sp-search-input::placeholder{color:var(--text-muted)}
    .sp-dropdown{
      position:absolute;top:calc(100%+4px);left:0;right:0;z-index:200;
      background:var(--bg-card);border:1px solid var(--border-base);
      border-radius:var(--r-md);box-shadow:var(--shadow-lg);
      max-height:360px;overflow-y:auto;display:none;
    }
    .sp-dropdown.open{display:block}
    .sp-item{
      display:flex;align-items:center;gap:12px;
      padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border-base);
      transition:background var(--t);
    }
    .sp-item:last-child{border-bottom:none}
    .sp-item:hover{background:var(--bg-muted)}
    .sp-item-icon{width:32px;height:32px;border-radius:50%;background:var(--bg-muted);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .sp-item-icon i{color:var(--mo-corona);font-size:.85rem}
    .sp-item-name{font-weight:600;color:var(--text-primary);font-size:.88rem}
    .sp-item-sci{font-style:italic;color:var(--text-muted);font-size:.75rem}
    .sp-item-family{font-size:.7rem;color:var(--text-muted)}
    .sp-item-code{font-size:.68rem;background:rgba(26,143,175,.15);color:var(--mo-corona);padding:2px 8px;border-radius:var(--r-pill);font-weight:700;white-space:nowrap}
    .sp-loading,.sp-empty{padding:16px;text-align:center;color:var(--text-muted);font-size:.85rem}
    .sp-empty i{display:block;font-size:1.5rem;margin-bottom:6px;opacity:.4}

    /* Add row UI */
    .add-esp-row{
      display:flex;align-items:center;gap:10px;margin-bottom:16px;
      padding:12px 14px;background:var(--bg-muted);border-radius:var(--r-md);
      border:1px solid var(--border-base);
    }
    .add-esp-info{flex:1}
    .add-esp-name{font-weight:700;color:var(--text-primary);font-size:.9rem}
    .add-esp-sci{font-style:italic;color:var(--text-muted);font-size:.77rem}
    .add-esp-cant{
      display:flex;align-items:center;gap:6px;
    }
    .cnt-btn{
      width:30px;height:30px;border-radius:50%;border:1.5px solid var(--border-base);
      background:var(--bg-card);color:var(--text-primary);
      font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;
      font-family:var(--font-body);transition:all var(--t);
    }
    .cnt-btn:hover{border-color:var(--mo-corona);color:var(--mo-corona)}
    .cnt-input{
      width:55px;text-align:center;padding:5px;
      background:var(--bg-input);border:1.5px solid var(--border-base);
      border-radius:var(--r-sm);color:var(--text-primary);
      font-size:.92rem;font-family:var(--font-body);outline:none;
    }
    .btn-add-sp{
      padding:8px 16px;border-radius:var(--r-md);
      background:var(--mo-corona);color:#fff;border:none;
      font-size:.83rem;font-weight:600;cursor:pointer;
      font-family:var(--font-body);white-space:nowrap;
      transition:background var(--t);
    }
    .btn-add-sp:hover{background:var(--accent-hover)}
    .btn-add-sp:disabled{background:var(--text-muted);cursor:not-allowed}

    /* Especies list */
    .esp-list{display:flex;flex-direction:column;gap:6px;min-height:40px}
    .esp-row{
      display:flex;align-items:center;gap:10px;
      padding:10px 14px;background:var(--bg-muted);
      border-radius:var(--r-md);border:1px solid var(--border-base);
    }
    .esp-handle{color:var(--text-muted);cursor:grab;font-size:.85rem;flex-shrink:0}
    .esp-info{flex:1}
    .esp-name{font-weight:600;color:var(--text-primary);font-size:.88rem}
    .esp-sci{font-style:italic;color:var(--text-muted);font-size:.74rem}
    .esp-cnt-box{display:flex;align-items:center;gap:5px}
    .esp-cnt-inp{
      width:55px;text-align:center;padding:5px;
      background:var(--bg-input);border:1.5px solid var(--border-base);
      border-radius:var(--r-sm);color:var(--text-primary);font-size:.88rem;
      font-family:var(--font-body);outline:none;
    }
    .esp-cnt-inp:focus{border-color:var(--mo-corona)}
    .btn-rem-esp{
      background:rgba(220,50,50,.1);color:#e05050;border:none;
      width:28px;height:28px;border-radius:var(--r-sm);cursor:pointer;
      display:flex;align-items:center;justify-content:center;font-size:.8rem;
      flex-shrink:0;transition:background var(--t);
    }
    .btn-rem-esp:hover{background:rgba(220,50,50,.22)}
    .esp-notas-inp{
      width:100%;padding:5px 8px;margin-top:4px;font-size:.78rem;
      background:var(--bg-input);border:1px solid var(--border-base);
      border-radius:var(--r-sm);color:var(--text-primary);font-family:var(--font-body);outline:none;
    }
    .esp-notas-inp:focus{border-color:var(--mo-corona)}
    .esp-count-label{font-size:.72rem;color:var(--text-muted);margin-top:4px}

    /* GPS bar */
    .btn-gps-lista{
      display:flex;align-items:center;justify-content:center;gap:8px;
      width:100%;padding:11px;border-radius:var(--r-md);
      background:rgba(26,143,175,.12);border:1.5px solid var(--mo-corona);
      color:var(--mo-corona);font-size:.88rem;font-weight:600;cursor:pointer;
      font-family:var(--font-body);transition:background var(--t);margin-bottom:16px;
    }
    .btn-gps-lista:hover{background:rgba(26,143,175,.2)}

    /* Bottom bar */
    .bottom-bar{
      position:fixed;bottom:0;left:0;right:0;z-index:90;
      background:var(--bg-card);border-top:1px solid var(--border-base);
      padding:12px 20px;display:flex;gap:10px;
      max-width:900px;margin:0 auto;
    }
    .btn-save-main{
      flex:1;padding:13px;border-radius:var(--r-md);
      background:var(--mo-corona);color:#fff;border:none;
      font-size:.95rem;font-weight:700;cursor:pointer;
      font-family:var(--font-body);
      display:flex;align-items:center;justify-content:center;gap:8px;
      transition:background var(--t),transform var(--t);
    }
    .btn-save-main:hover{background:var(--accent-hover);transform:translateY(-1px)}
    .btn-cancel{
      padding:13px 20px;border-radius:var(--r-md);
      background:var(--bg-muted);border:1px solid var(--border-base);
      color:var(--text-secondary);cursor:pointer;font-family:var(--font-body);font-size:.88rem;
    }
    .esp-empty-msg{
      text-align:center;padding:24px;color:var(--text-muted);font-size:.85rem;
      border:2px dashed var(--border-base);border-radius:var(--r-md);
    }
    .esp-empty-msg i{display:block;font-size:2rem;margin-bottom:8px;opacity:.3}
  </style>
</head>
<body>
<?php include __DIR__ . '/views/partials/navbar.php'; ?>

<!-- TOPBAR -->
<div class="topbar">
  <div class="topbar-left">
    <button class="btn-back-top" onclick="history.back()"><i class="fa-solid fa-arrow-left"></i></button>
    <div>
      <div class="topbar-title"><?= $editando ? '✏️ Editar Lista' : '📋 Nueva Lista de Campo' ?></div>
      <div class="topbar-sub"><?= e($nombre_user) ?> · <?= date('d/m/Y') ?></div>
    </div>
  </div>
  <button class="btn-save-top" form="form-lista" type="submit">
    <?= $editando ? 'Actualizar' : 'Guardar lista' ?>
  </button>
</div>

<div class="page-wrap">
<form id="form-lista" action="lista_guardar.php" method="POST">
  <?php if($editando): ?><input type="hidden" name="id_lista" value="<?= $edit_id ?>"><?php endif; ?>

  <!-- SECCIÓN 1: INFO GENERAL -->
  <div class="form-section">
    <div class="form-section-head">
      <i class="fa-solid fa-clipboard-list"></i>
      <h3>Información de la Salida</h3>
    </div>
    <div class="form-section-body">
      <div class="fgroup">
        <label class="flabel">Nombre de la lista <span class="req">*</span></label>
        <input type="text" name="nombre_lista" class="finput" required
               placeholder="Ej: Salida Valle Sagrado - Amanecer"
               value="<?= $editando ? e($lista['nombre_lista']) : '' ?>">
      </div>
      <div class="frow">
        <div class="fgroup">
          <label class="flabel">Fecha <span class="req">*</span></label>
          <input type="date" name="fecha_lista" class="finput" required
                 max="<?= date('Y-m-d') ?>"
                 value="<?= $editando ? e($lista['fecha_lista']) : date('Y-m-d') ?>">
        </div>
        <div class="fgroup">
          <label class="flabel">Hora inicio</label>
          <input type="time" name="hora_inicio" class="finput"
                 value="<?= $editando ? e($lista['hora_inicio']??'') : date('H:i') ?>">
        </div>
        <div class="fgroup">
          <label class="flabel">Duración (min)</label>
          <input type="number" name="duracion_min" class="finput" min="0" max="1440"
                 placeholder="60"
                 value="<?= $editando ? e($lista['duracion_min']??'') : '' ?>">
        </div>
      </div>

      <div class="fgroup">
        <label class="flabel">Tipo de protocolo</label>
        <div class="protocolo-chips">
          <?php $proto_actual = $editando ? ($lista['tipo_protocolo']??'libre') : 'libre'; ?>
          <div class="proto-chip <?= $proto_actual==='libre'?'selected':'' ?>" data-val="libre" onclick="selectProto(this)">🌿 Campo libre</div>
          <div class="proto-chip <?= $proto_actual==='estacionario'?'selected':'' ?>" data-val="estacionario" onclick="selectProto(this)">📍 Punto fijo</div>
          <div class="proto-chip <?= $proto_actual==='en_movimiento'?'selected':'' ?>" data-val="en_movimiento" onclick="selectProto(this)">🚶 En movimiento</div>
        </div>
        <input type="hidden" name="tipo_protocolo" id="h_proto" value="<?= e($proto_actual) ?>">
      </div>
    </div>
  </div>

  <!-- SECCIÓN 2: UBICACIÓN -->
  <div class="form-section">
    <div class="form-section-head">
      <i class="fa-solid fa-location-dot"></i>
      <h3>Ubicación</h3>
    </div>
    <div class="form-section-body">
      <button type="button" class="btn-gps-lista" id="btnGpsLista">
        <i class="fa-solid fa-location-crosshairs"></i> Usar mi ubicación GPS
      </button>
      <div class="fgroup">
        <label class="flabel">Nombre del lugar</label>
        <input type="text" name="lugar_nombre" id="lista_lugar" class="finput"
               placeholder="Ej: Valle Sagrado, Pisac"
               value="<?= $editando ? e($lista['lugar_nombre']??'') : '' ?>">
      </div>
      <div class="frow">
        <div class="fgroup">
          <label class="flabel">Latitud</label>
          <input type="number" step="any" name="lugar_lat" id="lista_lat" class="finput"
                 placeholder="-13.5226"
                 value="<?= $editando ? e($lista['lugar_lat']??'') : '' ?>">
        </div>
        <div class="fgroup">
          <label class="flabel">Longitud</label>
          <input type="number" step="any" name="lugar_lng" id="lista_lng" class="finput"
                 placeholder="-71.9673"
                 value="<?= $editando ? e($lista['lugar_lng']??'') : '' ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- SECCIÓN 3: ESPECIES -->
  <div class="form-section">
    <div class="form-section-head">
      <i class="fa-solid fa-feather-pointed"></i>
      <h3>Especies Observadas <span id="esp-counter" style="font-size:.8rem;color:var(--text-muted);font-weight:400">(0)</span></h3>
    </div>
    <div class="form-section-body">

      <!-- Buscador -->
      <div class="sp-search-wrap">
        <i class="fa-solid fa-magnifying-glass sp-search-icon"></i>
        <input type="text" id="spSearch" class="sp-search-input"
               placeholder="Busca un ave por nombre común o científico…" autocomplete="off">
        <div class="sp-dropdown" id="spDropdown"></div>
      </div>

      <!-- Selected species row to add -->
      <div class="add-esp-row" id="addEspRow" style="display:none">
        <div class="add-esp-info">
          <div class="add-esp-name" id="addEspName">—</div>
          <div class="add-esp-sci" id="addEspSci"></div>
        </div>
        <div class="add-esp-cant">
          <button type="button" class="cnt-btn" onclick="addCntMinus()">−</button>
          <input type="number" id="addCntInput" class="cnt-input" value="1" min="0" max="9999">
          <button type="button" class="cnt-btn" onclick="addCntPlus()">+</button>
        </div>
        <button type="button" class="btn-add-sp" id="btnAddSp" onclick="addEspToList()">
          <i class="fa-solid fa-plus"></i> Agregar
        </button>
      </div>

      <!-- Lista de especies añadidas -->
      <div class="esp-list" id="espList">
        <?php if(empty($especies_lista)): ?>
        <div class="esp-empty-msg" id="espEmptyMsg">
          <i class="fa-solid fa-dove"></i>
          Busca y agrega las especies que observaste en esta salida
        </div>
        <?php endif; ?>
      </div>

      <!-- Hidden inputs para submit -->
      <div id="esp-hidden-inputs"></div>
    </div>
  </div>

  <!-- SECCIÓN 4: NOTAS GENERALES -->
  <div class="form-section">
    <div class="form-section-head">
      <i class="fa-solid fa-note-sticky"></i>
      <h3>Notas de la Salida</h3>
    </div>
    <div class="form-section-body">
      <textarea name="notas" class="finput" rows="4"
                placeholder="Condiciones climáticas, hábitat visitado, observaciones generales…"><?= $editando ? e($lista['notas']??'') : '' ?></textarea>
    </div>
  </div>

</form>
</div>

<!-- BOTTOM BAR -->
<div class="bottom-bar">
  <button class="btn-cancel" onclick="history.back()">Cancelar</button>
  <button type="submit" form="form-lista" class="btn-save-main">
    <i class="fa-solid fa-<?= $editando ? 'floppy-disk' : 'paper-plane' ?>"></i>
    <?= $editando ? 'Actualizar Lista' : 'Guardar Lista' ?>
  </button>
</div>

<script>
// ── Protocolo chips
function selectProto(el) {
  document.querySelectorAll('.proto-chip').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('h_proto').value = el.dataset.val;
}

// ── GPS
document.getElementById('btnGpsLista').addEventListener('click', function() {
  if (!navigator.geolocation) { alert('GPS no disponible.'); return; }
  this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Obteniendo…';
  const btn = this;
  navigator.geolocation.getCurrentPosition(async pos => {
    const lat = pos.coords.latitude, lng = pos.coords.longitude;
    document.getElementById('lista_lat').value = lat.toFixed(6);
    document.getElementById('lista_lng').value = lng.toFixed(6);
    // Reverse geocode
    try {
      const r = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=es`);
      const d = await r.json();
      const label = d.display_name ? d.display_name.split(',').slice(0,3).join(', ') : '';
      if (label) document.getElementById('lista_lugar').value = label;
    } catch {}
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Ubicación obtenida ✓';
    setTimeout(() => btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Usar mi ubicación GPS', 3000);
  }, err => {
    btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Usar mi ubicación GPS';
    alert('No se pudo obtener la ubicación.');
  }, { enableHighAccuracy: true, timeout: 10000 });
});

// ── Buscar especie
let spSelected = null;
let spTimer = null;

const spSearch = document.getElementById('spSearch');
const spDropdown = document.getElementById('spDropdown');
const addEspRow  = document.getElementById('addEspRow');

spSearch.addEventListener('input', function() {
  clearTimeout(spTimer);
  const q = this.value.trim();
  if (q.length < 2) { closeDropdown(); return; }
  spDropdown.innerHTML = '<div class="sp-loading"><i class="fa-solid fa-spinner fa-spin"></i> Buscando…</div>';
  spDropdown.classList.add('open');
  spTimer = setTimeout(() => fetchSp(q), 200);
});

function fetchSp(q) {
  fetch('buscar_aves.php?q=' + encodeURIComponent(q) + '&all=1')
    .then(r => r.json())
    .then(data => {
      spDropdown.innerHTML = '';
      if (!data.length) {
        spDropdown.innerHTML = `<div class="sp-empty"><i class="fa-solid fa-dove"></i>Sin resultados para "${q}"<br><small>Intenta con otro nombre</small></div>`;
        return;
      }
      data.forEach(sp => {
        const el = document.createElement('div');
        el.className = 'sp-item';
        el.innerHTML = `
          <div class="sp-item-icon"><i class="fa-solid fa-feather"></i></div>
          <div style="flex:1">
            <div class="sp-item-name">${sp.primary_com_name}</div>
            <div class="sp-item-sci">${sp.sci_name || ''}</div>
            <div class="sp-item-family">${sp.family || ''}</div>
          </div>
          <span class="sp-item-code">${sp.species_code || ''}</span>`;
        el.addEventListener('mousedown', e => { e.preventDefault(); selectSp(sp); });
        spDropdown.appendChild(el);
      });
    })
    .catch(() => spDropdown.innerHTML = '<div class="sp-empty">Error de conexión</div>');
}

function selectSp(sp) {
  spSelected = sp;
  document.getElementById('addEspName').textContent = sp.primary_com_name;
  document.getElementById('addEspSci').textContent  = sp.sci_name || '';
  document.getElementById('addCntInput').value = 1;
  addEspRow.style.display = 'flex';
  spSearch.value = '';
  closeDropdown();
}

function closeDropdown() { spDropdown.classList.remove('open'); spDropdown.innerHTML = ''; }
document.addEventListener('click', e => { if (!spSearch.contains(e.target) && !spDropdown.contains(e.target)) closeDropdown(); });

function addCntMinus() { const i = document.getElementById('addCntInput'); i.value = Math.max(0, parseInt(i.value)||1 - 1); }
function addCntPlus()  { const i = document.getElementById('addCntInput'); i.value = (parseInt(i.value)||0) + 1; }

// ── Especie list management
const espRows = []; // { code, nombre, sci, cantidad, notas }

<?php if($editando && !empty($especies_lista)): ?>
// Pre-load existing species for edit mode
<?php foreach($especies_lista as $esp): ?>
espRows.push({
  code: <?= json_encode($esp['species_code'] ?? '') ?>,
  nombre: <?= json_encode($esp['nombre_comun']) ?>,
  sci: <?= json_encode($esp['sci_name'] ?? '') ?>,
  cantidad: <?= (int)$esp['cantidad'] ?>,
  notas: <?= json_encode($esp['notas_especie'] ?? '') ?>
});
<?php endforeach; ?>
renderEspList();
<?php endif; ?>

function addEspToList() {
  if (!spSelected) return;
  // Check duplicate
  if (espRows.find(r => r.code === spSelected.species_code && r.nombre === spSelected.primary_com_name)) {
    alert(`"${spSelected.primary_com_name}" ya está en la lista.`);
    addEspRow.style.display = 'none'; spSelected = null; return;
  }
  espRows.push({
    code:     spSelected.species_code || '',
    nombre:   spSelected.primary_com_name,
    sci:      spSelected.sci_name || '',
    cantidad: parseInt(document.getElementById('addCntInput').value) || 1,
    notas:    ''
  });
  addEspRow.style.display = 'none';
  spSelected = null;
  renderEspList();
}

function renderEspList() {
  const list = document.getElementById('espList');
  const hiddens = document.getElementById('esp-hidden-inputs');
  const counter = document.getElementById('esp-counter');
  list.innerHTML = '';
  hiddens.innerHTML = '';
  counter.textContent = `(${espRows.length})`;

  if (espRows.length === 0) {
    list.innerHTML = `<div class="esp-empty-msg" id="espEmptyMsg"><i class="fa-solid fa-dove"></i>Busca y agrega las especies que observaste en esta salida</div>`;
    return;
  }

  espRows.forEach((esp, idx) => {
    const row = document.createElement('div');
    row.className = 'esp-row';
    row.innerHTML = `
      <i class="fa-solid fa-grip-vertical esp-handle"></i>
      <div class="esp-info" style="flex:1">
        <div class="esp-name">${esp.nombre}</div>
        <div class="esp-sci">${esp.sci}</div>
        <input type="text" class="esp-notas-inp" placeholder="Notas de esta especie…"
               value="${esp.notas}" oninput="espRows[${idx}].notas=this.value">
      </div>
      <div class="esp-cnt-box">
        <button type="button" class="cnt-btn" onclick="espCntChg(${idx},-1)">−</button>
        <input type="number" class="esp-cnt-inp" value="${esp.cantidad}" min="0" max="9999"
               oninput="espRows[${idx}].cantidad=parseInt(this.value)||0;syncHiddens()">
        <button type="button" class="cnt-btn" onclick="espCntChg(${idx},1)">+</button>
      </div>
      <button type="button" class="btn-rem-esp" onclick="removeEsp(${idx})" title="Quitar"><i class="fa-solid fa-xmark"></i></button>
    `;
    list.appendChild(row);

    // Hidden inputs for form submission
    hiddens.innerHTML += `
      <input type="hidden" name="sp_code[]"   value="${esp.code}">
      <input type="hidden" name="sp_nombre[]" value="${esp.nombre.replace(/"/g,'&quot;')}">
      <input type="hidden" name="sp_sci[]"    value="${esp.sci.replace(/"/g,'&quot;')}">
      <input type="hidden" name="sp_cant[]"   value="${esp.cantidad}">
      <input type="hidden" name="sp_notas[]"  value="${(esp.notas||'').replace(/"/g,'&quot;')}">
    `;
  });
}

function syncHiddens() {
  const hiddens = document.getElementById('esp-hidden-inputs');
  hiddens.innerHTML = '';
  espRows.forEach(esp => {
    hiddens.innerHTML += `
      <input type="hidden" name="sp_code[]"   value="${esp.code}">
      <input type="hidden" name="sp_nombre[]" value="${esp.nombre.replace(/"/g,'&quot;')}">
      <input type="hidden" name="sp_sci[]"    value="${esp.sci.replace(/"/g,'&quot;')}">
      <input type="hidden" name="sp_cant[]"   value="${esp.cantidad}">
      <input type="hidden" name="sp_notas[]"  value="${(esp.notas||'').replace(/"/g,'&quot;')}">
    `;
  });
}

function espCntChg(idx, delta) {
  espRows[idx].cantidad = Math.max(0, (espRows[idx].cantidad||0) + delta);
  renderEspList();
}

function removeEsp(idx) {
  espRows.splice(idx, 1);
  renderEspList();
}

document.getElementById('form-lista').addEventListener('submit', function(e) {
  if (!document.querySelector('[name="nombre_lista"]').value.trim()) {
    e.preventDefault(); alert('El nombre de la lista es obligatorio.'); return;
  }
  if (espRows.length === 0) {
    if (!confirm('¿Guardar lista sin especies? Podrás añadirlas después.')) { e.preventDefault(); return; }
  }
  syncHiddens();
});
</script>
<script src="<?= JS_URL ?>"></script>
</body>
</html>
