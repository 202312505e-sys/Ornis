<?php
declare(strict_types=1);
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/rutas.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/RegistroModel.php';

Auth::requerir('auth.php');
$id_usuario = Auth::id();
$nombre     = Auth::nombre();

$modelo    = new RegistroModel($pdo);
$pagina    = max(1,(int)($_GET['pag']??1));
$resultado = $modelo->listar($id_usuario,$pagina,REGISTROS_POR_PAGINA);
$stats     = $modelo->estadisticas($id_usuario);
$mis_fotos = $modelo->fotos($id_usuario,6);

$msg_ok  = isset($_GET['ok'])  ? htmlspecialchars(urldecode($_GET['ok']),ENT_QUOTES,'UTF-8')  : null;
$msg_err = isset($_GET['err']) ? htmlspecialchars(urldecode($_GET['err']),ENT_QUOTES,'UTF-8') : null;

$stmt = $pdo->prepare("SELECT nombre,apellido,email,bio,avatar,ebird_username,fecha_registro FROM usuarios WHERE id_usuario=? LIMIT 1");
$stmt->execute([$id_usuario]);
$perfil = $stmt->fetch();
$fecha_miembro = $perfil ? date('d/m/Y',strtotime($perfil['fecha_registro'])) : '—';

function e(mixed $v):string{ return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Mi Panel — ORNIS</title>
  <link rel="stylesheet" href="<?= CSS_URL ?>"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script>(function(){const t=localStorage.getItem('ornis-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
  <style>
    .fotos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px}
    .foto-card{border-radius:var(--r-md);overflow:hidden;border:1px solid var(--border-base);background:var(--bg-card)}
    .foto-card img{width:100%;height:110px;object-fit:cover;display:block}
    .foto-card-info{padding:8px 10px}
    .foto-card-info strong{font-size:.81rem;color:var(--text-primary);display:block}
    .foto-card-info p{font-size:.74rem;color:var(--text-muted);margin:2px 0 0}
    .especie-fav{font-size:1rem!important;line-height:1.3!important}
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <a href="index.php" class="nav-logo"><img src="<?= IMG_URL ?>/ornisLogo.png" alt="ORNIS"/><span class="nav-brand">ORNIS</span></a>
  <ul class="nav-links">
    <li><a href="index.php">Inicio</a></li>
    <li><a href="galeria.php">Galería</a></li>
    <li><a href="dashboard.php" class="active-link">Mi Panel</a></li>
  </ul>
  <button class="theme-toggle"><span class="ico">🌙</span><span class="lbl">Oscuro</span></button>
  <span style="color:var(--mo-canela);font-weight:700;font-size:.88rem">Hola, <?= $nombre ?>!</span>
  <a href="logout.php" class="nav-cta" style="background:rgba(240,128,128,.18);color:#f08080"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
</nav>

<!-- LAYOUT DASHBOARD -->
<div class="dash-page">
  <div class="dash-layout">

    <!-- SIDEBAR -->
    <aside class="dash-sidebar">
      <div class="dash-avatar">
        <?php if(!empty($perfil['avatar'])):?>
          <img src="<?= UPLOADS_URL_ABS.e($perfil['avatar']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%" alt=""/>
        <?php else:?>
          <?= mb_strtoupper(mb_substr($nombre,0,1)) ?>
        <?php endif;?>
      </div>
      <div class="dash-uname"><?= $nombre ?></div>
      <div class="dash-umeta"><?= e($perfil['email']??'') ?><br/>Miembro desde <?= $fecha_miembro ?></div>

      <nav class="dash-nav">
        <a href="dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Mi Panel</a>
        <a href="registrar.php"><i class="fa-solid fa-plus"></i> Nuevo avistamiento</a>
        <a href="listas.php"><i class="fa-solid fa-clipboard-list"></i> Mis Listas</a>
        <a href="galeria_privada.php"><i class="fa-solid fa-images"></i> Mi galería de fotos</a>
        <a href="galeria.php"><i class="fa-solid fa-leaf"></i> Galería pública</a>
        <div style="border-top:1px solid var(--border-base);margin:16px 0"></div>
        <a href="logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
      </nav>

      <?php if(!empty($resultado['registros'])):$u=$resultado['registros'][0];?>
      <div style="margin-top:20px;border-top:1px solid var(--border-base);padding-top:14px">
        <p style="font-size:.73rem;color:var(--text-muted);margin-bottom:5px">Último avistamiento</p>
        <p style="font-weight:700;color:var(--mo-canela);font-size:.84rem"><?= e($u['nombre_comun']) ?></p>
        <p style="font-size:.77rem;color:var(--text-secondary)"><?= e($u['nombre_lugar']) ?></p>
        <p style="font-size:.73rem;color:var(--text-muted)"><?= date('d/m/Y',strtotime($u['fecha_avistamiento'])) ?></p>
      </div>
      <?php endif;?>
    </aside>

    <!-- MAIN -->
    <main class="dash-main">
      <div class="dash-title">Bienvenido, <?= $nombre ?> 🦜</div>

      <?php if($msg_ok):?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><?= $msg_ok ?></div><?php endif;?>
      <?php if($msg_err):?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><?= $msg_err ?></div><?php endif;?>

      <!-- STATS -->
      <div class="stat-cards">
        <div class="stat-card"><div class="ico"><i class="fa-solid fa-binoculars"></i></div><div class="num"><?= $stats['total_registros'] ?></div><div class="lbl">Avistamientos</div></div>
        <div class="stat-card"><div class="ico"><i class="fa-solid fa-feather-pointed"></i></div><div class="num"><?= $stats['total_especies'] ?></div><div class="lbl">Especies únicas</div></div>
        <div class="stat-card"><div class="ico"><i class="fa-solid fa-map-location-dot"></i></div><div class="num"><?= $stats['total_lugares'] ?></div><div class="lbl">Lugares</div></div>
        <div class="stat-card"><div class="ico"><i class="fa-solid fa-camera"></i></div><div class="num"><?= $stats['total_fotos'] ?></div><div class="lbl">Fotos subidas</div></div>
        <div class="stat-card"><div class="ico"><i class="fa-solid fa-calendar-days"></i></div><div class="num"><?= $stats['dias_activos'] ?></div><div class="lbl">Días activos</div></div>
        <?php if($stats['especie_favorita']):?>
        <div class="stat-card" style="border-left-color:var(--mo-canela)"><div class="ico" style="color:var(--mo-canela)"><i class="fa-solid fa-star"></i></div><div class="num especie-fav"><?= e($stats['especie_favorita']) ?></div><div class="lbl">Especie favorita</div></div>
        <?php endif;?>
      </div>

      <!-- TABLA AVISTAMIENTOS -->
      <div class="dash-panel">
        <h3><i class="fa-solid fa-list-ul" style="color:var(--mo-corona)"></i> Mis Avistamientos <span style="font-size:.78rem;color:var(--text-muted);font-weight:400">(<?= $stats['total_registros'] ?> total)</span></h3>

        <?php if(empty($resultado['registros'])):?>
          <div style="text-align:center;padding:44px 20px;color:var(--text-muted)">
            <div style="font-size:3rem;margin-bottom:12px;opacity:.35"><i class="fa-solid fa-dove"></i></div>
            <p style="margin-bottom:18px">Aún no tienes avistamientos registrados.</p>
            <a href="index.php#registro" class="btn-submit" style="text-decoration:none;display:inline-flex;max-width:280px;padding:12px 24px"><i class="fa-solid fa-plus"></i> Registrar mi primer avistamiento</a>
          </div>
        <?php else:?>
          <div style="overflow-x:auto">
            <table class="tabla-reg">
              <thead><tr><th>Foto</th><th>Especie</th><th>Lugar</th><th>Fecha</th><th>Cant.</th><th>Notas</th><th></th></tr></thead>
              <tbody>
              <?php foreach($resultado['registros'] as $r):
                $ruta = UPLOADS_PATH.($r['foto_ave']??'');
                $url  = UPLOADS_URL_ABS.e($r['foto_ave']??'');
              ?>
              <tr>
                <td><?php if(!empty($r['foto_ave'])&&file_exists($ruta)):?>
                  <img src="<?= $url ?>" class="foto-thumb" alt="<?= e($r['nombre_comun']) ?>"/>
                <?php else:?><div class="foto-ph"><i class="fa-solid fa-feather"></i></div><?php endif;?></td>
                <td>
                  <strong><?= e($r['nombre_comun']) ?></strong><br/>
                  <?php if(!empty($r['nombre_cientifico'])):?><em style="font-size:.77rem;color:var(--text-muted)"><?= e($r['nombre_cientifico']) ?></em><?php endif;?>
                </td>
                <td>📍 <?= e($r['nombre_lugar']) ?></td>
                <td style="white-space:nowrap"><?= date('d/m/Y',strtotime($r['fecha_avistamiento'])) ?></td>
                <td style="text-align:center"><span class="badge badge-g"><?= (int)$r['cantidad'] ?></span></td>
                <td style="max-width:150px;font-size:.81rem;color:var(--text-muted)"><?php $n=$r['notas']??''; echo e(mb_strlen($n)>55?mb_substr($n,0,55).'…':$n); ?></td>
                <td><a href="eliminar_avistamiento.php?id=<?= (int)$r['id_registro'] ?>" class="btn-del"><i class="fa-solid fa-trash"></i></a></td>
              </tr>
              <?php endforeach;?>
              </tbody>
            </table>
          </div>
          <?php if($resultado['total_paginas']>1):?>
          <div class="paginacion">
            <?php if($pagina>1):?><a href="?pag=<?= $pagina-1 ?>">‹ Anterior</a><?php endif;?>
            <?php for($i=1;$i<=$resultado['total_paginas'];$i++):?>
              <?php if($i===$pagina):?><span class="cur"><?= $i ?></span>
              <?php else:?><a href="?pag=<?= $i ?>"><?= $i ?></a><?php endif;?>
            <?php endfor;?>
            <?php if($pagina<$resultado['total_paginas']):?><a href="?pag=<?= $pagina+1 ?>">Siguiente ›</a><?php endif;?>
          </div>
          <?php endif;?>
        <?php endif;?>
      </div>

      <!-- PREVIEW FOTOS -->
      <?php if(!empty($mis_fotos)):?>
      <div class="dash-panel">
        <h3><i class="fa-solid fa-images" style="color:var(--mo-corona)"></i> Mis Fotografías</h3>
        <div class="fotos-grid">
          <?php foreach($mis_fotos as $f):?>
          <div class="foto-card">
            <img src="<?= UPLOADS_URL_ABS.e($f['foto_ave']) ?>" alt="<?= e($f['nombre_comun']) ?>"/>
            <div class="foto-card-info">
              <strong><?= e($f['nombre_comun']) ?></strong>
              <p>📍 <?= e(mb_substr($f['nombre_lugar'],0,22)) ?></p>
              <p><?= date('d/m/Y',strtotime($f['fecha_avistamiento'])) ?></p>
            </div>
          </div>
          <?php endforeach;?>
        </div>
        <?php if($stats['total_fotos']>6):?>
        <p style="text-align:center;margin-top:14px"><a href="galeria_privada.php" style="color:var(--mo-corona);font-size:.84rem;font-weight:600">Ver todas mis fotos (<?= $stats['total_fotos'] ?>) →</a></p>
        <?php endif;?>
      </div>
      <?php endif;?>

      <div style="text-align:center;padding:8px 0 4px">
        <a href="index.php#registro" class="btn-submit" style="text-decoration:none;display:inline-flex;max-width:280px;padding:12px 24px">
          <i class="fa-solid fa-plus"></i> Registrar nuevo avistamiento
        </a>
      </div>
    </main>
  </div>
</div>

<script src="<?= JS_URL ?>"></script>
</body>
</html>
