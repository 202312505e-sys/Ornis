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
$modelo     = new RegistroModel($pdo);

// AJAX toggle
if (isset($_POST['action']) && $_POST['action'] === 'toggle_publica') {
    header('Content-Type: application/json');
    $id_reg = (int)($_POST['id_registro'] ?? 0);
    if ($id_reg <= 0) { echo json_encode(['ok'=>false,'msg'=>'ID inválido.']); exit; }
    echo json_encode($modelo->togglePublica($id_reg, $id_usuario));
    exit;
}

$pagina    = max(1,(int)($_GET['pag']??1));
$resultado = $modelo->fotosPrivadas($id_usuario,$pagina,18);
$fotos     = $resultado['fotos'];
function e(mixed $v):string{ return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Mi Galería Privada — ORNIS</title>
  <link rel="stylesheet" href="<?= CSS_URL ?>"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script>(function(){const t=localStorage.getItem('ornis-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
  <style>
    .gp-page{padding-top:var(--nav-h);min-height:100vh;background:var(--bg-base)}
    .gp-header{background:linear-gradient(160deg,var(--mo-mascara) 0%,#0d2e1e 100%);padding:52px 24px 40px;text-align:center}
    .gp-header h1{color:#fff;font-family:var(--font-display);font-size:2.5rem;margin-bottom:8px}
    .gp-header p{color:rgba(255,255,255,.52)}
    .gp-body{max-width:1140px;margin:0 auto;padding:36px 24px 60px}
    .fotos-gp{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px}
    .fcard{background:var(--bg-card);border-radius:var(--r-lg);overflow:hidden;border:1px solid var(--border-base);box-shadow:var(--shadow-sm);position:relative}
    .fcard img{width:100%;height:200px;object-fit:cover;cursor:zoom-in;display:block;transition:transform .4s}
    .fcard:hover img{transform:scale(1.04)}
    .fcard-body{padding:14px 16px}
    .fcard-nombre{font-family:var(--font-display);font-size:1.05rem;font-weight:600;color:var(--text-primary);margin-bottom:2px}
    .fcard-sci{color:var(--text-muted);font-style:italic;font-size:.79rem;margin-bottom:10px}
    .fcard-meta{font-size:.8rem;color:var(--text-secondary);margin:3px 0;display:flex;align-items:center;gap:6px}
    .badge-ep{position:absolute;top:12px;left:12px;font-size:.68rem;font-weight:800;padding:4px 10px;border-radius:999px}
    .ep-pub{background:var(--mo-verde);color:#fff}
    .ep-priv{background:rgba(0,0,0,.6);color:rgba(255,255,255,.75)}
    .switch-row{display:flex;align-items:center;justify-content:space-between;margin-top:12px;padding-top:10px;border-top:1px solid var(--border-base)}
    .switch-lbl{font-size:.78rem;color:var(--text-muted)}
    .tog{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0}
    .tog input{opacity:0;width:0;height:0}
    .tog-sl{position:absolute;inset:0;background:var(--bg-muted);border-radius:24px;cursor:pointer;transition:.25s;border:1px solid var(--border-base)}
    .tog input:checked+.tog-sl{background:var(--mo-verde)}
    .tog-sl::before{content:'';position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;bottom:2px;left:2px;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.3)}
    .tog input:checked+.tog-sl::before{transform:translateX(18px)}
    .sw-fb{font-size:.74rem;color:var(--mo-lima);margin-top:6px;display:none}
    .lb{position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:3000;display:flex;align-items:center;justify-content:center;display:none}
    .lb.open{display:flex}
    .lb img{max-width:90vw;max-height:90vh;border-radius:var(--r-md);box-shadow:var(--shadow-xl)}
    .lb-x{position:absolute;top:20px;right:24px;background:rgba(255,255,255,.1);color:#fff;border:none;width:40px;height:40px;border-radius:50%;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center}
    .empty-gp{text-align:center;padding:70px 20px;color:var(--text-muted)}
    .empty-gp .ico{font-size:3rem;margin-bottom:14px;opacity:.35}
  </style>
</head>
<body>
<nav class="navbar" id="navbar">
  <a href="index.php" class="nav-logo"><img src="<?= IMG_URL ?>/ornisLogo.png" alt="ORNIS"/><span class="nav-brand">ORNIS</span></a>
  <ul class="nav-links">
    <li><a href="index.php">Inicio</a></li>
    <li><a href="galeria.php">Galería</a></li>
    <li><a href="dashboard.php">Mi Panel</a></li>
    <li><a href="galeria_privada.php" class="active-link">Mis Fotos</a></li>
  </ul>
  <button class="theme-toggle"><span class="ico">🌙</span><span class="lbl">Oscuro</span></button>
  <a href="logout.php" class="nav-cta" style="background:rgba(240,128,128,.18);color:#f08080">Salir</a>
</nav>

<div class="gp-page">
  <div class="gp-header">
    <h1>📷 Mi Galería de Fotos</h1>
    <p>Tus fotografías de avistamientos · <?= $resultado['total'] ?> imagen<?= $resultado['total']!=1?'es':'' ?></p>
  </div>

  <div class="gp-body">
    <?php if(empty($fotos)):?>
      <div class="empty-gp">
        <div class="ico"><i class="fa-solid fa-camera"></i></div>
        <h3 style="color:var(--text-secondary);margin-bottom:8px">Aún no tienes fotografías</h3>
        <p>Cuando registres un avistamiento con foto aparecerá aquí.</p>
        <a href="index.php#registro" class="btn-submit" style="text-decoration:none;display:inline-flex;max-width:260px;padding:12px 24px;margin-top:20px"><i class="fa-solid fa-plus"></i> Registrar con foto</a>
      </div>
    <?php else:?>
      <div class="fotos-gp">
        <?php foreach($fotos as $f):
          $url = UPLOADS_URL_ABS.e($f['foto_ave']);
          $pub = (bool)($f['is_public']??false);
        ?>
        <div class="fcard" id="card-<?= (int)$f['id_registro'] ?>">
          <span class="badge-ep <?= $pub?'ep-pub':'ep-priv' ?>" id="badge-<?= (int)$f['id_registro'] ?>">
            <i class="fa-solid fa-<?= $pub?'globe':'lock' ?>"></i> <?= $pub?'Pública':'Privada' ?>
          </span>
          <img src="<?= $url ?>" alt="<?= e($f['nombre_comun']) ?>" loading="lazy"
               onclick="abrirLb('<?= $url ?>')"/>
          <div class="fcard-body">
            <div class="fcard-nombre"><?= e($f['nombre_comun']) ?></div>
            <?php if(!empty($f['nombre_cientifico'])):?><div class="fcard-sci"><?= e($f['nombre_cientifico']) ?></div><?php endif;?>
            <div class="fcard-meta"><i class="fa-solid fa-location-dot" style="color:var(--mo-verde)"></i><?= e($f['nombre_lugar']) ?></div>
            <div class="fcard-meta"><i class="fa-regular fa-calendar" style="color:var(--mo-corona)"></i><?= date('d/m/Y',strtotime($f['fecha_avistamiento'])) ?></div>
            <?php if(!empty($f['familia'])):?><div class="fcard-meta"><i class="fa-solid fa-feather" style="color:var(--text-muted)"></i><span style="color:var(--text-muted)"><?= e($f['familia']) ?></span></div><?php endif;?>
            <div class="switch-row">
              <span class="switch-lbl" id="lbl-<?= (int)$f['id_registro'] ?>"><?= $pub?'Visible en galería':'Publicar en galería' ?></span>
              <label class="tog" aria-label="Publicar foto">
                <input type="checkbox" <?= $pub?'checked':'' ?> onchange="togglePub(<?= (int)$f['id_registro'] ?>,this)"/>
                <span class="tog-sl"></span>
              </label>
            </div>
            <div class="sw-fb" id="fb-<?= (int)$f['id_registro'] ?>"></div>
          </div>
        </div>
        <?php endforeach;?>
      </div>

      <?php if($resultado['total_paginas']>1):?>
      <div class="paginacion" style="margin-top:28px">
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
</div>

<!-- Lightbox -->
<div class="lb" id="lb" onclick="cerrarLb(event)">
  <button class="lb-x" onclick="cerrarLb()"><i class="fa-solid fa-xmark"></i></button>
  <img id="lb-img" src="" alt=""/>
</div>

<script>
function togglePub(id, cb) {
  const fd = new FormData();
  fd.append('action','toggle_publica'); fd.append('id_registro',id);
  fetch('galeria_privada.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    if(!d.ok){cb.checked=!cb.checked;alert(d.msg||'Error');return;}
    const p=d.is_public;
    const b=document.getElementById('badge-'+id);
    b.className='badge-ep '+(p?'ep-pub':'ep-priv');
    b.innerHTML=`<i class="fa-solid fa-${p?'globe':'lock'}"></i> ${p?'Pública':'Privada'}`;
    document.getElementById('lbl-'+id).textContent=p?'Visible en galería':'Publicar en galería';
    const fb=document.getElementById('fb-'+id);
    fb.textContent=d.msg; fb.style.display='block';
    setTimeout(()=>fb.style.display='none',3000);
  }).catch(()=>{cb.checked=!cb.checked;alert('Error de conexión.');});
}
function abrirLb(src){document.getElementById('lb-img').src=src;document.getElementById('lb').classList.add('open');}
function cerrarLb(e){if(!e||e.target===document.getElementById('lb')||e.target.closest('.lb-x'))document.getElementById('lb').classList.remove('open');}
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.getElementById('lb').classList.remove('open');});
</script>
<script src="<?= JS_URL ?>"></script>
</body></html>
