<?php
declare(strict_types=1);
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/rutas.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';
Auth::requerir('auth.php');

$id_usuario = Auth::id();
function e(mixed $v):string{ return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8'); }

$id_lista = (int)($_GET['id'] ?? 0);
if (!$id_lista) { header('Location: listas.php'); exit; }

$st = $pdo->prepare("SELECT * FROM listas_avistamiento WHERE id_lista=? AND id_usuario=?");
$st->execute([$id_lista, $id_usuario]);
$lista = $st->fetch();
if (!$lista) { header('Location: listas.php?err=' . urlencode('Lista no encontrada.')); exit; }

$st2 = $pdo->prepare("SELECT le.*, t.sci_name, t.family FROM lista_especies le LEFT JOIN especies_taxonomia t ON le.species_code=t.species_code WHERE le.id_lista=? ORDER BY le.orden ASC");
$st2->execute([$id_lista]);
$especies = $st2->fetchAll();

$msg_ok = isset($_GET['ok']) ? htmlspecialchars(urldecode($_GET['ok']),ENT_QUOTES,'UTF-8') : null;
$total_aves = array_sum(array_column($especies,'cantidad'));
$tipos_label = ['libre'=>'Campo libre','estacionario'=>'Punto fijo','en_movimiento'=>'En movimiento'];
$tipo = $lista['tipo_protocolo'] ?? 'libre';
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= e($lista['nombre_lista']) ?> — ORNIS</title>
  <link rel="stylesheet" href="<?= CSS_URL ?>"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script>(function(){const t=localStorage.getItem('ornis-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
  <style>
    .page-wrap{max-width:960px;margin:0 auto;padding:calc(var(--nav-h)+28px) 20px 60px}
    .back-link{display:inline-flex;align-items:center;gap:6px;color:var(--text-muted);font-size:.85rem;margin-bottom:20px;text-decoration:none}
    .back-link:hover{color:var(--mo-corona)}
    .lista-title{font-family:var(--font-display);font-size:2rem;color:var(--text-primary);margin-bottom:6px}
    .lista-subtitle{color:var(--text-muted);font-size:.9rem;display:flex;flex-wrap:wrap;gap:14px;margin-bottom:24px}
    .lista-subtitle span{display:flex;align-items:center;gap:5px}
    .actions-bar{display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap}
    .btn-action{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:var(--r-pill);font-size:.85rem;font-weight:600;cursor:pointer;border:none;font-family:var(--font-body);text-decoration:none;transition:var(--t)}
    .btn-edit-a{background:var(--mo-corona);color:#fff}.btn-edit-a:hover{background:var(--accent-hover)}
    .btn-del-a{background:rgba(220,50,50,.1);color:#e05050;border:1px solid rgba(220,50,50,.2)}.btn-del-a:hover{background:rgba(220,50,50,.2)}
    .btn-back-a{background:var(--bg-muted);color:var(--text-secondary);border:1px solid var(--border-base)}.btn-back-a:hover{background:var(--border-base)}

    .stat-row{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:24px}
    .stat-chip{background:var(--bg-card);border:1px solid var(--border-base);border-radius:var(--r-md);padding:12px 18px;text-align:center}
    .stat-chip .n{font-size:1.5rem;font-weight:700;color:var(--mo-corona);font-family:var(--font-display)}
    .stat-chip .l{font-size:.72rem;color:var(--text-muted)}

    .section-card{background:var(--bg-card);border:1px solid var(--border-base);border-radius:var(--r-lg);overflow:hidden;margin-bottom:20px}
    .sc-head{padding:14px 20px;border-bottom:1px solid var(--border-base);display:flex;align-items:center;gap:10px}
    .sc-head h3{font-size:.92rem;font-weight:700;color:var(--mo-corona)}
    .sc-head i{color:var(--mo-corona);width:18px}
    .sc-body{padding:20px}

    table.esp-table{width:100%;border-collapse:collapse;font-size:.87rem}
    .esp-table thead{background:var(--bg-muted)}
    .esp-table th{padding:10px 14px;text-align:left;color:var(--text-muted);font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-base)}
    .esp-table td{padding:10px 14px;border-bottom:1px solid var(--border-base);vertical-align:middle}
    .esp-table tr:last-child td{border-bottom:none}
    .esp-table tr:hover td{background:var(--bg-muted)}
    .esp-name-cell .name{font-weight:600;color:var(--text-primary)}
    .esp-name-cell .sci{font-style:italic;color:var(--text-muted);font-size:.77rem}
    .cant-badge{display:inline-block;padding:3px 10px;border-radius:var(--r-pill);background:rgba(26,143,175,.15);color:var(--mo-corona);font-weight:700;font-size:.8rem}
    .notas-cell{color:var(--text-muted);font-size:.8rem;max-width:200px}
    #mapLista{width:100%;height:280px;border-radius:var(--r-md)}
    .alert-ok{padding:12px 18px;border-radius:var(--r-md);margin-bottom:20px;font-size:.9rem;display:flex;align-items:center;gap:8px;background:rgba(72,194,122,.12);color:#48C27A;border:1px solid rgba(72,194,122,.2)}
    .notas-box{background:var(--bg-muted);border-radius:var(--r-md);padding:14px 16px;color:var(--text-secondary);font-size:.88rem;line-height:1.6}
  </style>
</head>
<body>
<?php include __DIR__ . '/views/partials/navbar.php'; ?>

<div class="page-wrap">
  <a href="listas.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Mis Listas</a>

  <?php if($msg_ok): ?><div class="alert-ok"><i class="fa-solid fa-check-circle"></i> <?= $msg_ok ?></div><?php endif; ?>

  <h1 class="lista-title"><?= e($lista['nombre_lista']) ?></h1>
  <div class="lista-subtitle">
    <span><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($lista['fecha_lista'])) ?></span>
    <?php if($lista['hora_inicio']): ?><span><i class="fa-regular fa-clock"></i> <?= e(substr($lista['hora_inicio'],0,5)) ?></span><?php endif; ?>
    <?php if($lista['duracion_min']): ?><span><i class="fa-solid fa-stopwatch"></i> <?= (int)$lista['duracion_min'] ?> minutos</span><?php endif; ?>
    <span><i class="fa-solid fa-route"></i> <?= e($tipos_label[$tipo] ?? $tipo) ?></span>
    <?php if($lista['lugar_nombre']): ?><span><i class="fa-solid fa-location-dot"></i> <?= e($lista['lugar_nombre']) ?></span><?php endif; ?>
  </div>

  <div class="actions-bar">
    <a href="listas.php" class="btn-action btn-back-a"><i class="fa-solid fa-list"></i> Mis listas</a>
    <a href="lista_form.php?edit=<?= $id_lista ?>" class="btn-action btn-edit-a"><i class="fa-solid fa-pen"></i> Editar</a>
    <button onclick="confirmarEliminar()" class="btn-action btn-del-a"><i class="fa-solid fa-trash"></i> Eliminar</button>
  </div>

  <div class="stat-row">
    <div class="stat-chip"><div class="n"><?= count($especies) ?></div><div class="l">Especies</div></div>
    <div class="stat-chip"><div class="n"><?= number_format((int)$total_aves) ?></div><div class="l">Aves contadas</div></div>
    <?php
    $familias = array_filter(array_unique(array_column($especies,'family')));
    ?>
    <div class="stat-chip"><div class="n"><?= count($familias) ?></div><div class="l">Familias</div></div>
  </div>

  <!-- Mapa (si hay coords) -->
  <?php if(!empty($lista['lugar_lat']) && !empty($lista['lugar_lng'])): ?>
  <div class="section-card" style="margin-bottom:20px">
    <div class="sc-head"><i class="fa-solid fa-map"></i><h3>Ubicación</h3></div>
    <div id="mapLista"></div>
  </div>
  <?php endif; ?>

  <!-- Especies -->
  <div class="section-card">
    <div class="sc-head"><i class="fa-solid fa-feather-pointed"></i><h3>Especies Observadas (<?= count($especies) ?>)</h3></div>
    <div class="sc-body" style="padding:0">
      <?php if(empty($especies)): ?>
      <p style="padding:20px;color:var(--text-muted);text-align:center">Sin especies registradas en esta lista.</p>
      <?php else: ?>
      <table class="esp-table">
        <thead><tr><th>#</th><th>Especie</th><th>Familia</th><th>Cantidad</th><th>Notas</th></tr></thead>
        <tbody>
          <?php foreach($especies as $i => $esp): ?>
          <tr>
            <td style="color:var(--text-muted)"><?= $i+1 ?></td>
            <td class="esp-name-cell">
              <div class="name"><?= e($esp['nombre_comun']) ?></div>
              <div class="sci"><?= e($esp['sci_name'] ?? '') ?></div>
            </td>
            <td style="color:var(--text-muted);font-size:.8rem"><?= e($esp['family']??'') ?></td>
            <td><span class="cant-badge"><?= $esp['cantidad']==0 ? 'X' : (int)$esp['cantidad'] ?></span></td>
            <td class="notas-cell"><?= e($esp['notas_especie']??'') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Notas generales -->
  <?php if(!empty($lista['notas'])): ?>
  <div class="section-card">
    <div class="sc-head"><i class="fa-solid fa-note-sticky"></i><h3>Notas de la Salida</h3></div>
    <div class="sc-body"><div class="notas-box"><?= nl2br(e($lista['notas'])) ?></div></div>
  </div>
  <?php endif; ?>
</div>

<?php if(!empty($lista['lugar_lat'])): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  const lat = <?= (float)$lista['lugar_lat'] ?>, lng = <?= (float)$lista['lugar_lng'] ?>;
  const map = L.map('mapLista', { scrollWheelZoom: false }).setView([lat, lng], 14);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:19, attribution:'© OpenStreetMap' }).addTo(map);
  L.marker([lat, lng]).addTo(map).bindPopup('<?= e(addslashes($lista['lugar_nombre']??'Ubicación')) ?>').openPopup();
</script>
<?php endif; ?>
<script>
function confirmarEliminar(){if(confirm('¿Eliminar esta lista? Esta acción no se puede deshacer.'))window.location.href='lista_eliminar.php?id=<?= $id_lista ?>';}
</script>
<script src="<?= JS_URL ?>"></script>
</body>
</html>
