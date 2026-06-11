<?php
// ============================================================
//  registrar.php — Formulario CRUD de avistamiento (eBird style)
//  Modo: nuevo (GET) o editar (GET ?edit=ID)
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/rutas.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/RegistroModel.php';

Auth::requerir('auth.php');

$id_usuario = Auth::id();
$nombre     = Auth::nombre();
$modelo     = new RegistroModel($pdo);

// Modo editar
$edit_id = (int)($_GET['edit'] ?? 0);
$editando = false;
$registro_edit = null;
if ($edit_id > 0) {
    $registro_edit = $modelo->obtener($edit_id, $id_usuario);
    if ($registro_edit) $editando = true;
}

// Mensajes
$msg_err = isset($_GET['err']) ? htmlspecialchars(urldecode($_GET['err']), ENT_QUOTES, 'UTF-8') : null;

function e(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $editando ? 'Editar' : 'Registrar' ?> Avistamiento — ORNIS</title>
  <link rel="stylesheet" href="<?= CSS_URL ?>"/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
  <script>(function(){const t=localStorage.getItem('ornis-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
  <style>
    body { padding-top: var(--nav-h); font-family: var(--font-body); background: var(--bg-base); color: var(--text-primary); }
    /* ── Reg top/bottom bars */
    .reg-topbar { position:fixed; top:var(--nav-h); left:0; right:0; z-index:100; display:flex; align-items:center; justify-content:space-between; padding:12px 20px; box-shadow:0 2px 8px rgba(0,0,0,.25); }
    .reg-topbar-left { display:flex; align-items:center; gap:14px; }
    .reg-topbar-title { font-size:1rem; font-weight:700; color:#fff; }
    .reg-topbar-sub { font-size:.8rem; opacity:.8; color:#fff; }
    .btn-topbar-back { background:transparent; border:none; color:#fff; font-size:1.1rem; cursor:pointer; padding:4px 8px; }
    /* ── Main */
    .reg-main { max-width:780px; margin:0 auto; padding:78px 16px 90px; }
    /* ── Sections */
    .reg-section { border-radius:14px; margin-bottom:16px; overflow:hidden; }
    .reg-section-header { padding:13px 20px; border-bottom-width:1px; border-bottom-style:solid; display:flex; align-items:center; gap:10px; }
    .reg-section-body { padding:20px; }
    /* ── Species search */
    .species-search-wrap { position:relative; }
    .species-search-icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); font-size:.9rem; }
    .species-search-input { width:100%; padding:13px 14px 13px 40px; border-width:1.5px; border-style:solid; border-radius:10px; font-size:.95rem; font-family:var(--font-body); outline:none; transition:border-color .2s,box-shadow .2s; }
    .species-search-clear { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; display:none; }
    .species-list { position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:500; border-radius:10px; box-shadow:0 8px 32px rgba(0,0,0,.3); border-width:1px; border-style:solid; max-height:380px; overflow-y:auto; display:none; }
    .species-list.open { display:block; }
    .species-item { display:flex; align-items:center; gap:12px; padding:11px 14px; cursor:pointer; border-bottom-width:1px; border-bottom-style:solid; transition:background .15s; }
    .species-item:last-child { border-bottom:none; }
    .species-item-icon { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .species-item-info { flex:1; }
    .species-item-name { font-weight:600; font-size:.92rem; }
    .species-item-sci { font-style:italic; font-size:.77rem; }
    .species-item-family { font-size:.7rem; }
    .species-item-code { font-size:.7rem; padding:2px 8px; border-radius:20px; font-weight:700; }
    .species-loading, .species-empty { padding:16px; text-align:center; font-size:.85rem; }
    .species-empty i { font-size:1.5rem; margin-bottom:8px; display:block; opacity:.4; }
    .species-selected { display:none; border-radius:10px; padding:11px 14px; margin-top:8px; align-items:center; gap:12px; border-width:1px; border-style:solid; }
    .species-selected.show { display:flex; }
    .species-selected-info { flex:1; }
    .species-selected-name { font-weight:700; font-size:.98rem; }
    .species-selected-sci { font-style:italic; font-size:.8rem; }
    .species-selected-remove { background:none; border:none; cursor:pointer; font-size:.9rem; padding:4px; }
    /* ── Count */
    .conteo-row { display:flex; align-items:center; gap:12px; }
    .conteo-btn { width:44px; height:44px; border-radius:50%; border-width:2px; border-style:solid; font-size:1.3rem; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; background:transparent; }
    .conteo-input { flex:1; text-align:center; border-width:2px; border-style:solid; border-radius:10px; padding:10px; font-size:1.4rem; font-weight:700; font-family:var(--font-body); outline:none; }
    .conteo-badge { padding:8px 16px; border-radius:10px; font-size:.85rem; font-weight:600; white-space:nowrap; }
    .presente-row { display:flex; align-items:center; gap:10px; margin-bottom:18px; }
    .presente-cb { width:22px; height:22px; border-width:2px; border-style:solid; border-radius:6px; cursor:pointer; appearance:none; position:relative; transition:all .2s; }
    .presente-cb:checked::after { content:'✓'; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color:#fff; font-size:.9rem; }
    /* ── Field */
    .field-group { margin-bottom:18px; }
    .field-label { display:block; font-size:.8rem; font-weight:600; margin-bottom:6px; letter-spacing:.3px; }
    .field-label .req { color:#e05050; margin-left:2px; }
    .field-input { width:100%; padding:12px 14px; border-width:1.5px; border-style:solid; border-radius:10px; font-size:.93rem; font-family:var(--font-body); outline:none; transition:border-color .2s,box-shadow .2s; }
    textarea.field-input { resize:vertical; min-height:100px; }
    .field-row { display:flex; gap:12px; }
    .field-row .field-group { flex:1; }
    /* ── Clima / comp chips */
    .clima-chips, .comp-chips { display:flex; gap:8px; flex-wrap:wrap; }
    .clima-chip, .comp-chip { padding:8px 16px; border-radius:50px; border-width:1.5px; border-style:solid; cursor:pointer; font-size:.84rem; transition:all .2s; display:flex; align-items:center; gap:5px; font-family:var(--font-body); }
    /* ── Foto */
    .foto-upload { position:relative; border-width:2px; border-style:dashed; border-radius:12px; min-height:110px; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; transition:border-color .2s; overflow:hidden; }
    .foto-upload input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; }
    .foto-upload-ui { text-align:center; pointer-events:none; padding:20px; }
    .foto-upload-ui i { font-size:2rem; margin-bottom:8px; }
    .foto-upload-ui p { font-size:.85rem; }
    .foto-upload-ui span { font-size:.75rem; }
    .foto-preview { display:none; }
    .foto-preview img { width:100%; max-height:200px; object-fit:cover; display:block; }
    .foto-preview-bar { padding:10px 14px; background:rgba(0,0,0,.6); display:flex; align-items:center; justify-content:space-between; }
    .foto-preview-name { color:#fff; font-size:.82rem; }
    .foto-preview-remove { background:#e53e3e; color:#fff; border:none; padding:4px 10px; border-radius:6px; cursor:pointer; font-size:.78rem; }
    .foto-existing { display:flex; align-items:center; gap:12px; padding:12px; border-radius:10px; margin-bottom:10px; }
    .foto-existing img { width:60px; height:60px; object-fit:cover; border-radius:8px; }
    /* ── GPS button */
    .btn-gps { align-self:flex-end; margin-bottom:18px; padding:12px 16px; border-radius:10px; border-width:1.5px; border-style:solid; cursor:pointer; font-size:.85rem; display:flex; align-items:center; gap:6px; white-space:nowrap; transition:background .2s; font-family:var(--font-body); background:transparent; }
    .gps-row { display:flex; gap:10px; }
    .gps-row .field-group { flex:1; }
    /* ── Bottom bar */
    .reg-bottombar { position:fixed; bottom:0; left:0; right:0; z-index:100; padding:13px 20px; display:flex; gap:12px; max-width:780px; margin:0 auto; border-top-width:1px; border-top-style:solid; }
    .btn-save-main { flex:1; padding:14px; border-radius:10px; border:none; font-size:.98rem; font-weight:700; cursor:pointer; font-family:var(--font-body); display:flex; align-items:center; justify-content:center; gap:8px; transition:background .2s,transform .2s; }
    .btn-save-main:hover { transform:translateY(-1px); }
    .btn-cancel { padding:14px 20px; border-radius:10px; border-width:1.5px; border-style:solid; cursor:pointer; font-size:.9rem; font-family:var(--font-body); background:transparent; }
    /* ── Error */
    .reg-error { border-radius:10px; padding:13px 16px; margin-bottom:16px; display:flex; align-items:center; gap:8px; font-size:.9rem; }
    @media(max-width:600px){.field-row,.gps-row{flex-direction:column}.reg-topbar-sub{display:none}}
  </style>
</head>
<body>

<!-- NAVBAR -->
<?php include __DIR__ . '/views/partials/navbar.php'; ?>

<!-- TOP BAR estilo eBird -->
<div class="reg-topbar">
  <div class="reg-topbar-left">
    <button class="btn-topbar-back" onclick="history.back()" title="Volver">
      <i class="fa-solid fa-arrow-left"></i>
    </button>
    <div>
      <div class="reg-topbar-title">
        <?= $editando ? '✏️ Editar Avistamiento' : '+ Nuevo Avistamiento' ?>
      </div>
      <div class="reg-topbar-sub">
        <?php if ($editando): ?>
          Modificando: <?= e($registro_edit['nombre_comun']) ?>
        <?php else: ?>
          <?= date('d/m/Y') ?> · <?= e($nombre) ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <button class="btn-topbar-save" form="form-avistamiento" type="submit">
    <?= $editando ? 'Actualizar' : 'Guardar' ?>
  </button>
</div>

<!-- MAIN CONTENT -->
<div class="reg-main">

  <?php if ($msg_err): ?>
  <div class="reg-error">
    <i class="fa-solid fa-circle-exclamation"></i> <?= $msg_err ?>
  </div>
  <?php endif; ?>

  <form id="form-avistamiento"
        action="<?= $editando ? 'editar_avistamiento.php' : 'guardar_avistamiento.php' ?>"
        method="POST" enctype="multipart/form-data">

    <?php if ($editando): ?>
      <input type="hidden" name="id_registro" value="<?= $edit_id ?>">
    <?php endif; ?>

    <!-- ══ SECCIÓN 1: ESPECIE ══ -->
    <div class="reg-section">
      <div class="reg-section-header">
        <i class="fa-solid fa-feather-pointed"></i>
        <h3>Especie</h3>
      </div>
      <div class="reg-section-body">

        <div class="field-group">
          <label class="field-label">
            Busca el ave <span class="req">*</span>
            <span style="font-weight:400;color:var(--text-light);">(nombre común o científico)</span>
          </label>
          <div class="species-search-wrap" id="searchWrap">
            <i class="fa-solid fa-magnifying-glass species-search-icon"></i>
            <input type="text" id="speciesSearch" class="species-search-input"
                   placeholder="Ej: Gallito de las Rocas, Rupicola…"
                   autocomplete="off"
                   value="<?= $editando && !empty($registro_edit['nombre_comun']) ? e($registro_edit['nombre_comun']) : '' ?>">
            <button type="button" class="species-search-clear" id="searchClear">
              <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="species-list" id="speciesList"></div>
          </div>

          <!-- Selected species display -->
          <div class="species-selected <?= ($editando && !empty($registro_edit['nombre_comun'])) ? 'show' : '' ?>" id="speciesSelected">
            <div class="species-item-icon">
              <i class="fa-solid fa-feather-pointed"></i>
            </div>
            <div class="species-selected-info">
              <div class="species-selected-name" id="selectedName">
                <?= $editando ? e($registro_edit['nombre_comun']) : '' ?>
              </div>
              <div class="species-selected-sci" id="selectedSci">
                <?= $editando ? e($registro_edit['nombre_cientifico'] ?? '') : '' ?>
              </div>
            </div>
            <button type="button" class="species-selected-remove" id="removeSpecies" title="Cambiar especie">
              <i class="fa-solid fa-pen"></i>
            </button>
          </div>

          <!-- Hidden inputs for form submission -->
          <input type="hidden" name="nombre_comun"      id="h_nombre_comun"
                 value="<?= $editando ? e($registro_edit['nombre_comun']) : '' ?>" required>
          <input type="hidden" name="species_code"      id="h_species_code"
                 value="<?= $editando ? e($registro_edit['species_code'] ?? '') : '' ?>">
          <input type="hidden" name="nombre_cientifico" id="h_nombre_cientifico"
                 value="<?= $editando ? e($registro_edit['nombre_cientifico'] ?? '') : '' ?>">
        </div>

        <!-- Conteo -->
        <div class="field-group">
          <label class="field-label">Número observado <span class="req">*</span></label>
          <div class="conteo-row">
            <button type="button" class="conteo-btn" id="btn-menos">−</button>
            <input type="number" name="cantidad" id="conteoInput" class="conteo-input"
                   min="1" max="9999" value="<?= $editando ? (int)$registro_edit['cantidad'] : 1 ?>" required>
            <button type="button" class="conteo-btn" id="btn-mas">+</button>
            <div class="conteo-badge" id="conteoBadge">
              <?= $editando ? (int)$registro_edit['cantidad'] : 1 ?> individuo(s)
            </div>
          </div>
          <!-- Checkbox "Presente" like eBird -->
          <div class="presente-row" style="margin-top:12px;">
            <input type="checkbox" id="cb_presente" class="presente-cb"
                   onchange="togglePresente(this)">
            <label for="cb_presente" class="presente-label">
              ☑ Presente (sin conteo exacto)
            </label>
          </div>
        </div>

        <!-- Detalles/Notas de la especie -->
        <div class="field-group">
          <label class="field-label" for="notas">Detalles adicionales</label>
          <textarea name="notas" id="notas" class="field-input"
                    placeholder="Comportamiento, plumaje, vocalizaciones, contexto del avistamiento…"
                    rows="3"><?= $editando ? e($registro_edit['notas'] ?? '') : '' ?></textarea>
        </div>

      </div>
    </div>

    <!-- ══ SECCIÓN 2: FOTO ══ -->
    <div class="reg-section">
      <div class="reg-section-header">
        <i class="fa-solid fa-camera"></i>
        <h3>Fotografía <span style="font-weight:400;color:var(--text-light);font-size:.82rem;">(opcional)</span></h3>
      </div>
      <div class="reg-section-body">

        <?php if ($editando && !empty($registro_edit['foto_ave'])): ?>
        <div class="foto-existing">
          <img src="<?= UPLOADS_URL_ABS . e($registro_edit['foto_ave']) ?>" alt="foto actual">
          <div class="foto-existing-info">
            <strong>Foto actual</strong>
            <p>Sube una nueva para reemplazarla</p>
          </div>
        </div>
        <?php endif; ?>

        <div class="foto-upload" id="fotoUploadZone">
          <input type="file" name="foto_ave" id="fotoInput"
                 accept="image/jpeg,image/png,image/webp,image/heic,.heic"
                 onchange="previewFoto(event)">
          <div class="foto-upload-ui" id="fotoUI">
            <i class="fa-solid fa-image"></i>
            <p>Arrastra o <strong style="color:var(--mo-corona)">selecciona</strong> una foto</p>
            <span>JPG · PNG · WEBP · HEIC · Máx 10 MB</span>
          </div>
          <div class="foto-preview" id="fotoPreview">
            <img id="fotoPreviewImg" alt="preview">
            <div class="foto-preview-bar">
              <span class="foto-preview-name" id="fotoPreviewName"></span>
              <button type="button" class="foto-preview-remove" onclick="removeFoto()">
                Quitar foto
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ══ SECCIÓN 3: UBICACIÓN ══ -->
    <div class="reg-section">
      <div class="reg-section-header">
        <i class="fa-solid fa-location-dot"></i>
        <h3>Ubicación</h3>
      </div>
      <div class="reg-section-body">

        <div class="field-group">
          <label class="field-label" for="nombre_lugar">
            Nombre del lugar <span class="req">*</span>
          </label>
          <input type="text" name="nombre_lugar" id="nombre_lugar" class="field-input"
                 placeholder="Ej: Bosque de Polylepis, Valle Sagrado km 45…"
                 value="<?= $editando ? e($registro_edit['nombre_lugar']) : '' ?>" required>
        </div>

        <div class="gps-row">
          <div class="field-group">
            <label class="field-label" for="latitud">Latitud <span class="req">*</span></label>
            <input type="number" step="any" name="latitud" id="latitud" class="field-input"
                   placeholder="-13.5226"
                   value="<?= $editando ? e($registro_edit['latitud'] ?? '') : '' ?>" required>
          </div>
          <div class="field-group">
            <label class="field-label" for="longitud">Longitud <span class="req">*</span></label>
            <input type="number" step="any" name="longitud" id="longitud" class="field-input"
                   placeholder="-71.9673"
                   value="<?= $editando ? e($registro_edit['longitud'] ?? '') : '' ?>" required>
          </div>
          <button type="button" class="btn-gps" onclick="obtenerGPS()" title="Usar mi ubicación actual">
            <i class="fa-solid fa-crosshairs"></i> GPS
          </button>
        </div>
        <p style="font-size:.75rem;color:var(--text-light);margin-top:-10px;margin-bottom:14px;">
          <i class="fa-solid fa-info-circle"></i> Haz clic en GPS para usar tu ubicación actual
        </p>

      </div>
    </div>

    <!-- ══ SECCIÓN 4: FECHA Y HORA ══ -->
    <div class="reg-section">
      <div class="reg-section-header">
        <i class="fa-regular fa-calendar"></i>
        <h3>Fecha y Hora</h3>
      </div>
      <div class="reg-section-body">
        <div class="field-row">
          <div class="field-group">
            <label class="field-label" for="fecha_avistamiento">
              Fecha <span class="req">*</span>
            </label>
            <input type="date" name="fecha_avistamiento" id="fecha_avistamiento" class="field-input"
                   value="<?= $editando ? e($registro_edit['fecha_avistamiento']) : date('Y-m-d') ?>"
                   max="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="field-group">
            <label class="field-label" for="hora">Hora</label>
            <input type="time" name="hora" id="hora" class="field-input"
                   value="<?= $editando ? e($registro_edit['hora'] ?? '') : date('H:i') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- ══ SECCIÓN 5: CONDICIONES ══ -->
    <div class="reg-section">
      <div class="reg-section-header">
        <i class="fa-solid fa-cloud-sun"></i>
        <h3>Condiciones del Campo</h3>
      </div>
      <div class="reg-section-body">

        <div class="field-group">
          <label class="field-label">Clima</label>
          <div class="clima-chips">
            <?php
            $climas = [
              'soleado'  => ['☀️', 'Soleado'],
              'nublado'  => ['☁️', 'Nublado'],
              'lluvioso' => ['🌧️', 'Lluvioso'],
              'neblina'  => ['🌫️', 'Neblina'],
            ];
            $clima_actual = $editando ? ($registro_edit['clima'] ?? '') : '';
            foreach ($climas as $val => [$ico, $lbl]):
            ?>
            <div class="clima-chip <?= $clima_actual === $val ? 'selected' : '' ?>"
                 data-val="<?= $val ?>" onclick="selectClima(this)">
              <?= $ico ?> <?= $lbl ?>
            </div>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="clima" id="h_clima" value="<?= e($clima_actual) ?>">
        </div>

        <div class="field-group">
          <label class="field-label">Comportamiento observado</label>
          <div class="comp-chips">
            <?php
            $comps = [
              'volando'        => ['🦅', 'Volando'],
              'alimentandose'  => ['🌿', 'Alimentándose'],
              'anidando'       => ['🪺', 'Anidando'],
              'en_reposo'      => ['😴', 'En reposo'],
              'cantando'       => ['🎵', 'Cantando'],
            ];
            $comp_actual = $editando ? ($registro_edit['comportamiento'] ?? '') : '';
            foreach ($comps as $val => [$ico, $lbl]):
            ?>
            <div class="comp-chip <?= $comp_actual === $val ? 'selected' : '' ?>"
                 data-val="<?= $val ?>" onclick="selectComp(this)">
              <?= $ico ?> <?= $lbl ?>
            </div>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="comportamiento" id="h_comp" value="<?= e($comp_actual) ?>">
        </div>

      </div>
    </div>

  </form><!-- /form-avistamiento -->
</div><!-- /reg-main -->

<!-- BOTTOM BAR -->
<div class="reg-bottombar">
  <button type="button" class="btn-cancel" onclick="history.back()">
    Cancelar
  </button>
  <button type="submit" form="form-avistamiento" class="btn-save-main">
    <i class="fa-solid fa-<?= $editando ? 'floppy-disk' : 'paper-plane' ?>"></i>
    <?= $editando ? 'Actualizar Avistamiento' : 'Guardar Avistamiento' ?>
  </button>
</div>

<script>
// ── Búsqueda de especies eBird-style
let searchTimer = null;
let searchActive = false;

const searchInput  = document.getElementById('speciesSearch');
const speciesList  = document.getElementById('speciesList');
const speciesSel   = document.getElementById('speciesSelected');
const selectedName = document.getElementById('selectedName');
const selectedSci  = document.getElementById('selectedSci');
const searchClear  = document.getElementById('searchClear');
const hNombre      = document.getElementById('h_nombre_comun');
const hCode        = document.getElementById('h_species_code');
const hSci         = document.getElementById('h_nombre_cientifico');

// Si ya hay especie seleccionada (modo editar), ocultar el search
<?php if ($editando && !empty($registro_edit['nombre_comun'])): ?>
searchActive = true;
searchInput.style.display = 'none';
speciesSel.classList.add('show');
<?php endif; ?>

searchInput.addEventListener('input', function() {
  const q = this.value.trim();
  searchClear.style.display = q ? 'block' : 'none';
  clearTimeout(searchTimer);
  if (q.length < 2) { closeList(); return; }
  speciesList.innerHTML = '<div class="species-loading"><i class="fa-solid fa-spinner fa-spin"></i> Buscando…</div>';
  speciesList.classList.add('open');
  searchTimer = setTimeout(() => fetchSpecies(q), 220);
});

searchInput.addEventListener('focus', function() {
  if (this.value.trim().length >= 2) speciesList.classList.add('open');
});

searchClear.addEventListener('click', function() {
  searchInput.value = '';
  searchClear.style.display = 'none';
  closeList();
});

document.getElementById('removeSpecies').addEventListener('click', function() {
  speciesSel.classList.remove('show');
  searchInput.style.display = '';
  searchInput.value = '';
  searchInput.focus();
  hNombre.value = '';
  hCode.value   = '';
  hSci.value    = '';
  searchActive = false;
});

function fetchSpecies(q) {
  fetch('buscar_aves.php?q=' + encodeURIComponent(q) + '&all=1')
    .then(r => r.json())
    .then(data => {
      if (!data.length) {
        speciesList.innerHTML = `<div class="species-empty">
          <i class="fa-solid fa-dove"></i>
          No se encontraron especies para "<strong>${q}</strong>".<br>
          <small>Puedes escribir el nombre manualmente</small>
        </div>`;
        // Allow manual entry
        addManualOption(q);
        return;
      }
      speciesList.innerHTML = '';
      data.forEach(sp => {
        const item = document.createElement('div');
        item.className = 'species-item';
        item.innerHTML = `
          <div class="species-item-icon"><i class="fa-solid fa-feather"></i></div>
          <div class="species-item-info">
            <div class="species-item-name">${sp.primary_com_name}</div>
            <div class="species-item-sci">${sp.sci_name || ''}</div>
            <div class="species-item-family">${sp.family || ''}</div>
          </div>
          <span class="species-item-code">${sp.species_code || ''}</span>
        `;
        item.addEventListener('mousedown', (e) => {
          e.preventDefault();
          selectSpecies(sp.primary_com_name, sp.sci_name || '', sp.species_code || '');
        });
        speciesList.appendChild(item);
      });
    })
    .catch(() => {
      speciesList.innerHTML = '<div class="species-empty">Error de conexión</div>';
    });
}

function addManualOption(q) {
  const manual = document.createElement('div');
  manual.className = 'species-item';
  manual.style.borderTop = '1px solid #e0f0e0';
  manual.innerHTML = `
    <div class="species-item-icon"><i class="fa-solid fa-pen"></i></div>
    <div class="species-item-info">
      <div class="species-item-name">Usar "<strong>${q}</strong>"</div>
      <div class="species-item-sci">Entrada manual</div>
    </div>
  `;
  manual.addEventListener('mousedown', (e) => {
    e.preventDefault();
    selectSpecies(q, '', '');
  });
  speciesList.appendChild(manual);
}

function selectSpecies(nombre, sci, code) {
  hNombre.value = nombre;
  hCode.value   = code;
  hSci.value    = sci;
  selectedName.textContent = nombre;
  selectedSci.textContent  = sci;
  speciesSel.classList.add('show');
  searchInput.style.display = 'none';
  searchInput.value = '';
  searchClear.style.display = 'none';
  closeList();
  searchActive = true;
}

function closeList() {
  speciesList.classList.remove('open');
  speciesList.innerHTML = '';
}

document.addEventListener('click', function(e) {
  if (!document.getElementById('searchWrap').contains(e.target)) closeList();
});

// ── Conteo
const conteoInput  = document.getElementById('conteoInput');
const conteoBadge  = document.getElementById('conteoBadge');
document.getElementById('btn-menos').addEventListener('click', () => {
  let v = parseInt(conteoInput.value) || 1;
  if (v > 1) { conteoInput.value = v - 1; updateBadge(v-1); }
});
document.getElementById('btn-mas').addEventListener('click', () => {
  let v = parseInt(conteoInput.value) || 1;
  conteoInput.value = v + 1; updateBadge(v+1);
});
conteoInput.addEventListener('input', () => updateBadge(parseInt(conteoInput.value) || 1));
function updateBadge(n) {
  conteoBadge.textContent = n + ' individuo' + (n === 1 ? '' : 's');
}

function togglePresente(cb) {
  if (cb.checked) {
    conteoInput.value     = 0;
    conteoInput.disabled  = true;
    conteoBadge.textContent = 'Presente';
    document.getElementById('btn-menos').disabled = true;
    document.getElementById('btn-mas').disabled   = true;
  } else {
    conteoInput.value     = 1;
    conteoInput.disabled  = false;
    updateBadge(1);
    document.getElementById('btn-menos').disabled = false;
    document.getElementById('btn-mas').disabled   = false;
  }
}

// ── Foto preview
function previewFoto(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(ev) {
    document.getElementById('fotoUI').style.display = 'none';
    document.getElementById('fotoPreview').style.display = 'block';
    document.getElementById('fotoPreviewImg').src = ev.target.result;
    document.getElementById('fotoPreviewName').textContent = file.name;
  };
  reader.readAsDataURL(file);
}
function removeFoto() {
  document.getElementById('fotoInput').value = '';
  document.getElementById('fotoUI').style.display = '';
  document.getElementById('fotoPreview').style.display = 'none';
}

// ── GPS — obtiene coords Y nombre del lugar vía Nominatim
function obtenerGPS() {
  if (!navigator.geolocation) { alert('GPS no disponible en este navegador.'); return; }
  const btn = document.querySelector('.btn-gps');
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Obteniendo…';
  btn.disabled = true;
  navigator.geolocation.getCurrentPosition(
    async pos => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      document.getElementById('latitud').value  = lat.toFixed(6);
      document.getElementById('longitud').value = lng.toFixed(6);
      // Reverse geocode with Nominatim
      try {
        const r = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=es`);
        const d = await r.json();
        if (d.display_name) {
          const label = d.display_name.split(',').slice(0, 3).join(', ');
          document.getElementById('nombre_lugar').value = label;
        }
      } catch(e) { /* silent fail */ }
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Obtenido ✓';
      btn.disabled = false;
      setTimeout(() => btn.innerHTML = '<i class="fa-solid fa-crosshairs"></i> GPS', 3000);
    },
    err => {
      btn.innerHTML = '<i class="fa-solid fa-crosshairs"></i> GPS';
      btn.disabled = false;
      alert('No se pudo obtener la ubicación: ' + err.message);
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

// ── Clima chips
function selectClima(el) {
  document.querySelectorAll('.clima-chip').forEach(c => c.classList.remove('selected'));
  el.classList.toggle('selected');
  document.getElementById('h_clima').value = el.classList.contains('selected') ? el.dataset.val : '';
}

// ── Comportamiento chips
function selectComp(el) {
  document.querySelectorAll('.comp-chip').forEach(c => c.classList.remove('selected'));
  el.classList.toggle('selected');
  document.getElementById('h_comp').value = el.classList.contains('selected') ? el.dataset.val : '';
}

// ── Form validation
document.getElementById('form-avistamiento').addEventListener('submit', function(e) {
  if (!document.getElementById('h_nombre_comun').value.trim()) {
    e.preventDefault();
    searchInput.style.display = '';
    searchInput.focus();
    searchInput.style.borderColor = '#e53e3e';
    setTimeout(() => searchInput.style.borderColor = '', 2000);
    alert('Selecciona o escribe el nombre del ave primero.');
    return;
  }
  if (!document.getElementById('nombre_lugar').value.trim()) {
    e.preventDefault();
    document.getElementById('nombre_lugar').focus();
    alert('El nombre del lugar es obligatorio.');
  }
});
</script>
<script src="<?= JS_URL ?>"></script>
</body>
</html>
