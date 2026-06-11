<?php
declare(strict_types=1);
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/rutas.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/RegistroModel.php';

Auth::iniciarSesion();
$logueado   = Auth::verificar();
$id_usuario = $logueado ? Auth::id() : 0;
$mis_fotos  = [];
if ($logueado) { $m = new RegistroModel($pdo); $mis_fotos = $m->fotos($id_usuario, 40); }

function e(mixed $v):string{ return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8'); }

$catalogo = [
  ['nombre'=>'Gallito de las Rocas','sci'=>'Rupicola peruvianus','familia'=>'Cotingidae','orden'=>'Passeriformes','tag'=>'Ave Nacional','habitat'=>'bosque','altitud'=>'1 000–2 400 m','conservacion'=>'LC','comportamiento'=>'Territorial','descripcion'=>'Símbolo nacional del Perú. Su plumaje naranja intenso es inconfundible en bosques nublados andinos.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v1/asset/56869081/900']],
  ['nombre'=>'Cóndor Andino','sci'=>'Vultur gryphus','familia'=>'Cathartidae','orden'=>'Cathartiformes','tag'=>'Especie Emblema','habitat'=>'rapaz','altitud'=>'2 500–5 000 m','conservacion'=>'VU','comportamiento'=>'Planea en corrientes térmicas','descripcion'=>'Una de las aves voladoras más grandes del mundo, con envergadura de hasta 3.2 m.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v1/asset/115347711/2400']],
  ['nombre'=>'Motmot Andino','sci'=>'Momotus aequatorialis','familia'=>'Momotidae','orden'=>'Coraciiformes','tag'=>'Cola Raqueta','habitat'=>'bosque','altitud'=>'1 200–3 000 m','conservacion'=>'LC','comportamiento'=>'Columpia la cola en péndulo','descripcion'=>'Ave icónica que inspira la paleta de ORNIS. Cola en forma de raqueta, plumaje turquesa y corona azul.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v1/asset/56523561/900','https://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Momotus_aequatorialis_-_Andean_Motmot.jpg/800px-Momotus_aequatorialis_-_Andean_Motmot.jpg']],
  ['nombre'=>'Colibrí Gigante','sci'=>'Patagona gigas','familia'=>'Trochilidae','orden'=>'Apodiformes','tag'=>'El más grande','habitat'=>'colibri','altitud'=>'1 500–4 000 m','conservacion'=>'LC','comportamiento'=>'Visita flores de Puya','descripcion'=>'El colibrí más grande del mundo. Frecuenta valles interandinos con plantas de puya.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v2/asset/601413861/900']],
  ['nombre'=>'Tangara del Paraíso','sci'=>'Tangara chilensis','familia'=>'Thraupidae','orden'=>'Passeriformes','tag'=>'Colores Vibrantes','habitat'=>'bosque','altitud'=>'600–1 400 m','conservacion'=>'LC','comportamiento'=>'Bandadas mixtas','descripcion'=>'Una de las aves más coloridas de Sudamérica. Habita bordes de bosque tropical en las yungas.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v2/asset/322197901/900']],
  ['nombre'=>'Pato de Torrente','sci'=>'Merganetta armata','familia'=>'Anatidae','orden'=>'Anseriformes','tag'=>'Ríos Andinos','habitat'=>'acuatica','altitud'=>'1 000–4 500 m','conservacion'=>'LC','comportamiento'=>'Nada en corrientes rápidas','descripcion'=>'Especialista de ríos turbulentos andinos. Su cuerpo aerodinámico le permite luchar contra la corriente.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v1/asset/115695161/900']],
  ['nombre'=>'Tordo de Montaña','sci'=>'Turdus fuscater','familia'=>'Turdidae','orden'=>'Passeriformes','tag'=>'Cantor','habitat'=>'cantor','altitud'=>'2 400–4 000 m','conservacion'=>'LC','comportamiento'=>'Canto melodioso al amanecer','descripcion'=>'El tordo más alto de los Andes. Su canto complejo resuena en valles de altura.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v2/asset/249551571/900']],
  ['nombre'=>'Perdiz de Pecho Castaño','sci'=>'Nothoprocta ornata','familia'=>'Tinamidae','orden'=>'Tinamiformes','tag'=>'Puna','habitat'=>'puna','altitud'=>'3 500–4 800 m','conservacion'=>'LC','comportamiento'=>'Camina sigilosamente','descripcion'=>'Tinamú adaptado a la puna alta. Difícil de ver por su coloración críptica.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v2/asset/658233909/900']],
  ['nombre'=>'Colibrí Rutilante','sci'=>'Aglaeactis cupripennis','familia'=>'Trochilidae','orden'=>'Apodiformes','tag'=>'Colibrí','habitat'=>'colibri','altitud'=>'2 500–4 500 m','conservacion'=>'LC','comportamiento'=>'Defiende flores agresivamente','descripcion'=>'Su espalda muestra iridiscencias cobre y púrpura únicas al sol andino.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v2/asset/495775691/1200']],
  ['nombre'=>'Aguilucho Variable','sci'=>'Geranoaetus polyosoma','familia'=>'Accipitridae','orden'=>'Accipitriformes','tag'=>'Rapaz','habitat'=>'rapaz','altitud'=>'0–4 500 m','conservacion'=>'LC','comportamiento'=>'Planea sobre laderas','descripcion'=>'La rapaz más común de los Andes. Existe en múltiples morfos de color.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v1/asset/115695161/900']],
  ['nombre'=>'Carpintero de los Andes','sci'=>'Colaptes rupicola','familia'=>'Picidae','orden'=>'Piciformes','tag'=>'Tierra','habitat'=>'pajaro','altitud'=>'3 000–4 800 m','conservacion'=>'LC','comportamiento'=>'Excava nidos en taludes','descripcion'=>'Inusual carpintero terrestre que anida en el suelo. Abundante en la puna peruana.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v1/asset/56869081/900']],
  ['nombre'=>'Zambullidor del Titicaca','sci'=>'Rollandia microptera','familia'=>'Podicipedidae','orden'=>'Podicipediformes','tag'=>'Endémica','habitat'=>'acuatica','altitud'=>'3 800 m','conservacion'=>'EN','comportamiento'=>'Incapaz de volar','descripcion'=>'Endémica del Lago Titicaca. En peligro por contaminación del lago.','fotos'=>['https://cdn.download.ams.birds.cornell.edu/api/v1/asset/56523561/320']],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Galería de Aves — ORNIS</title>
  <link rel="stylesheet" href="<?= CSS_URL ?>"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script>(function(){const t=localStorage.getItem('ornis-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
  <style>
    .acc-section{background:var(--bg-muted);padding:60px 0 50px}
    .cta-bot{background:var(--mo-mascara);padding:70px 24px;text-align:center}
    .cta-bot h2{color:#fff;font-family:var(--font-display);font-size:2.1rem;margin-bottom:12px}
    .cta-bot p{color:rgba(255,255,255,.55);margin-bottom:26px}
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <a href="index.php" class="nav-logo"><img src="<?= IMG_URL ?>/ornisLogo.png" alt="ORNIS"/><span class="nav-brand">ORNIS</span></a>
  <ul class="nav-links">
    <li><a href="index.php">Inicio</a></li>
    <li><a href="galeria.php" class="active-link">Galería</a></li>
    <li><a href="index.php#registro">Registrar</a></li>
    <li><a href="index.php#footer">Contacto</a></li>
    <?php if($logueado):?><li><a href="dashboard.php" class="nav-accent">Mi Panel</a></li><li><a href="logout.php" class="nav-danger">Salir</a></li>
    <?php else:?><li><a href="auth.php" style="color:var(--mo-turq)">Ingresar</a></li><?php endif;?>
  </ul>
  <button class="theme-toggle"><span class="ico">🌙</span><span class="lbl">Oscuro</span></button>
  <a href="index.php#registro" class="nav-cta">+ Registrar ave</a>
</nav>

<div class="galeria-page">

  <!-- HERO GALERÍA -->
  <div class="galeria-hero" style="padding-top:calc(var(--nav-h)+44px)">
    <h1>Galería de Aves</h1>
    <p>Especies registradas en Cusco y los Andes del Sur · Fuente: eBird / Cornell Lab</p>
  </div>

  <!-- ACCORDION -->
  <div class="acc-section">
    <div class="section-header fade-in" style="padding:0 24px;margin-bottom:32px"><h2>Aves Emblemáticas</h2><p>Haz clic en cada panel para explorar la especie</p></div>
    <div class="accordion fade-in">
      <?php foreach(array_slice($catalogo,0,6) as $i=>$a):?>
      <div class="acc-panel <?= $i===0?'active':'' ?>">
        <img src="<?= e($a['fotos'][0]) ?>" alt="<?= e($a['nombre']) ?>" loading="lazy"/>
        <div class="acc-info">
          <span class="acc-tag"><?= e($a['tag']) ?></span>
          <h3><?= e($a['nombre']) ?></h3>
          <p><em><?= e($a['sci']) ?></em> · <?= e($a['familia']) ?></p>
          <p style="margin-top:4px;font-size:.76rem"><?= e(mb_substr($a['descripcion'],0,88)) ?>…</p>
        </div>
      </div>
      <?php endforeach;?>
    </div>
  </div>

  <!-- TABS -->
  <div style="background:var(--bg-base);padding-top:46px">
    <div class="tabs-bar">
      <button class="tab-btn active" data-tab="tab-catalogo"><i class="fa-solid fa-layer-group" style="margin-right:5px"></i> Catálogo (<?= count($catalogo) ?>)</button>
      <?php if($logueado):?><button class="tab-btn" data-tab="tab-mis-fotos"><i class="fa-solid fa-camera" style="margin-right:5px"></i> Mis Fotos (<?= count($mis_fotos) ?>)</button><?php endif;?>
    </div>

    <!-- TAB CATÁLOGO -->
    <div id="tab-catalogo" class="tab-panel active">
      <div class="galeria-toolbar">
        <div class="search-pill">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="buscarEspecie" placeholder="Buscar especie, familia, hábitat…" autocomplete="off"/>
        </div>
        <button id="filtrosToggle" class="filtros-toggle"><i class="fa-solid fa-sliders"></i> Filtros <span class="ico">▼</span></button>
      </div>

      <div class="filtros-wrap">
        <div id="filtrosPanel" class="filtros-panel">
          <div class="fg"><label>Hábitat</label><select id="filtroHabitat"><option value="">Todos</option><option value="bosque">Bosque</option><option value="acuatica">Acuáticas</option><option value="rapaz">Rapaces</option><option value="colibri">Colibrís</option><option value="cantor">Cantoras</option><option value="pajaro">Paseriformes</option><option value="puna">Puna</option></select></div>
          <div class="fg"><label>Conservación</label><select id="filtroConservacion"><option value="">Todos</option><option value="LC">LC – Preocupación menor</option><option value="NT">NT – Casi amenazada</option><option value="VU">VU – Vulnerable</option><option value="EN">EN – En peligro</option></select></div>
          <div class="fg"><label>Orden taxonómico</label><select id="filtroOrden"><option value="">Todos</option><option value="Passeriformes">Passeriformes</option><option value="Apodiformes">Apodiformes</option><option value="Accipitriformes">Accipitriformes</option><option value="Coraciiformes">Coraciiformes</option><option value="Anseriformes">Anseriformes</option><option value="Piciformes">Piciformes</option><option value="Tinamiformes">Tinamiformes</option></select></div>
          <div class="filtros-btns">
            <button class="btn-aplicar" id="btnAplicarFiltros">Aplicar</button>
            <button class="btn-limpiar" id="btnLimpiarFiltros">Limpiar</button>
          </div>
        </div>
      </div>

      <div class="chips-row">
        <span class="chip active" data-f="">Todas</span>
        <span class="chip" data-f="bosque">🌿 Bosque</span>
        <span class="chip" data-f="colibri">🪶 Colibrís</span>
        <span class="chip" data-f="rapaz">🦅 Rapaces</span>
        <span class="chip" data-f="acuatica">💧 Acuáticas</span>
        <span class="chip" data-f="cantor">🎵 Cantoras</span>
        <span class="chip" data-f="puna">⛰️ Puna</span>
        <span class="chip" data-f="pajaro">🐦 Paseriformes</span>
      </div>

      <div class="res-count"><strong id="countRes"><?= count($catalogo) ?></strong> especies encontradas</div>

      <div class="galeria-grid" id="galeriaGrid">
        <?php foreach($catalogo as $a):
          $jd = json_encode(['nombre'=>$a['nombre'],'sci'=>$a['sci'],'familia'=>$a['familia'],'orden'=>$a['orden'],
            'tag'=>$a['tag'],'habitat'=>$a['habitat'],'altitud'=>$a['altitud'],'conservacion'=>$a['conservacion'],
            'comportamiento'=>$a['comportamiento'],'descripcion'=>$a['descripcion'],'fotos'=>$a['fotos'],
            'conteo'=>'—','clima'=>'—','notas'=>$a['descripcion'],'ubicacion'=>'Región Cusco','fecha'=>'2025-2026'],
            JSON_HEX_APOS|JSON_HEX_QUOT);
        ?>
        <div class="gal-card"
             data-hab="<?= e($a['habitat']) ?>"
             data-cons="<?= e($a['conservacion']) ?>"
             data-ord="<?= e($a['orden']) ?>"
             data-txt="<?= strtolower(e($a['nombre'].' '.$a['sci'].' '.$a['familia'])) ?>"
             onclick='openModalAve(<?= $jd ?>)'>
          <img src="<?= e($a['fotos'][0]) ?>" alt="<?= e($a['nombre']) ?>" loading="lazy"/>
          <div class="gal-body">
            <div class="gal-nombre"><?= e($a['nombre']) ?></div>
            <div class="gal-sci"><?= e($a['sci']) ?></div>
          </div>
          <div class="gal-meta">
            <span class="badge badge-g"><?= e($a['tag']) ?></span>
            <span class="badge badge-b"><?= e($a['conservacion']) ?></span>
            <?php if(count($a['fotos'])>1):?><span class="badge badge-c"><i class="fa-solid fa-images"></i> <?= count($a['fotos']) ?></span><?php endif;?>
          </div>
        </div>
        <?php endforeach;?>
      </div>
    </div><!-- /tab-catalogo -->

    <!-- TAB MIS FOTOS -->
    <?php if($logueado):?>
    <div id="tab-mis-fotos" class="tab-panel">
      <?php if(empty($mis_fotos)):?>
        <div style="text-align:center;padding:80px 20px;color:var(--text-muted)">
          <div style="font-size:3rem;margin-bottom:14px;opacity:.4">📷</div>
          <h3 style="color:var(--text-secondary);margin-bottom:8px">Aún no tienes fotografías</h3>
          <p>Cuando registres un avistamiento con foto aparecerá aquí.</p>
          <a href="index.php#registro" style="display:inline-block;margin-top:20px;padding:12px 28px;border:1.5px solid var(--accent);color:var(--accent);border-radius:999px;font-weight:600">Registrar con foto</a>
        </div>
      <?php else:?>
        <div class="galeria-toolbar" style="padding-top:0">
          <div class="search-pill">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="buscarMisFotos" placeholder="Buscar en mis fotos…" autocomplete="off"/>
          </div>
        </div>
        <div class="galeria-grid" id="misFotosGrid">
          <?php foreach($mis_fotos as $f):
            $jd = json_encode(['nombre'=>$f['nombre_comun'],'sci'=>$f['nombre_cientifico']??'',
              'familia'=>'—','orden'=>'—','tag'=>'Mi foto','habitat'=>'—','altitud'=>'—',
              'conservacion'=>'—','comportamiento'=>$f['comportamiento']??'—',
              'descripcion'=>$f['notas']??'','fotos'=>[UPLOADS_URL_ABS.($f['foto_ave']??'')],
              'conteo'=>$f['cantidad']??1,'clima'=>$f['clima']??'—','notas'=>$f['notas']??'—',
              'ubicacion'=>$f['nombre_lugar']??'—','fecha'=>$f['fecha_avistamiento']??'—'],
              JSON_HEX_APOS|JSON_HEX_QUOT);
          ?>
          <div class="gal-card mifoto-i" data-n="<?= strtolower(e($f['nombre_comun'])) ?>" onclick='openModalAve(<?= $jd ?>)'>
            <img src="<?= UPLOADS_URL_ABS.e($f['foto_ave']) ?>" alt="<?= e($f['nombre_comun']) ?>" loading="lazy"/>
            <div class="gal-body">
              <div class="gal-nombre"><?= e($f['nombre_comun']) ?></div>
              <div class="gal-sci"><?= e($f['nombre_cientifico']??'') ?></div>
            </div>
            <div class="gal-meta">
              <span class="badge badge-g">📍 <?= e(mb_substr($f['nombre_lugar'],0,20)) ?></span>
              <span class="badge badge-b">📅 <?= date('d/m/Y',strtotime($f['fecha_avistamiento'])) ?></span>
            </div>
          </div>
          <?php endforeach;?>
        </div>
      <?php endif;?>
    </div>
    <?php endif;?>
  </div><!-- /bg-base -->

  <!-- TABLA RECIENTES -->
  <div class="tabla-section">
    <div class="section-header fade-in"><h2>Avistamientos Recientes</h2><p>Últimos registros de la comunidad ORNIS</p></div>
    <div class="tabla-wrap">
      <table class="tabla-av">
        <thead><tr><th>#</th><th>Ave</th><th>Científico</th><th>Ubicación</th><th>Fecha</th><th>Clima</th><th>Cant.</th></tr></thead>
        <tbody>
          <?php foreach([
            ['001','Gallito de las Rocas','Rupicola peruvianus','San Luis, Cusco','12/05/2025','☀️',2],
            ['002','Cóndor Andino','Vultur gryphus','Cañón del Apurímac','15/05/2025','☁️',1],
            ['003','Motmot Andino','Momotus aequatorialis','Bosque Polylepis','17/05/2025','🌫️',2],
            ['004','Colibrí Gigante','Patagona gigas','Valle Sagrado','18/05/2025','☀️',3],
            ['005','Pato de Torrente','Merganetta armata','Río Urubamba','20/05/2025','🌧️',2],
            ['006','Tangara del Paraíso','Tangara chilensis','Wayra Pampa','25/05/2025','☀️',5],
            ['007','Tordo de Montaña','Turdus fuscater','Pisac','28/05/2025','☁️',4],
          ] as $r):?>
          <tr>
            <td style="color:var(--text-muted);font-size:.79rem"><?= $r[0] ?></td>
            <td><strong><?= $r[1] ?></strong></td>
            <td style="font-style:italic;color:var(--text-muted)"><?= $r[2] ?></td>
            <td>📍 <?= $r[3] ?></td><td><?= $r[4] ?></td><td><?= $r[5] ?></td>
            <td><span class="badge badge-g"><?= $r[6] ?></span></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="cta-bot">
    <h2>¿Observaste una nueva especie?</h2>
    <p>Contribuye al banco de datos de ORNIS registrando tu avistamiento.</p>
    <a href="index.php#registro" class="btn-primary">+ Registrar avistamiento</a>
  </div>

</div><!-- /galeria-page -->

<!-- FOOTER -->
<footer id="footer">
  <div class="footer-inner">
    <div class="footer-brand"><div class="logo-w"><img src="<?= IMG_URL ?>/ornisLogo.png" alt="ORNIS"/><span class="fn">ORNIS</span></div><p>Plataforma de ciencia ciudadana para observación de aves en los Andes del sur del Perú.</p></div>
    <div><h4>Plataforma</h4><ul><li><a href="index.php">Inicio</a></li><li><a href="galeria.php">Galería</a></li><li><a href="index.php#registro">Registrar</a></li></ul></div>
    <div><h4>Proyecto</h4><ul><li><a href="#">Univ. Andina del Cusco</a></li><li><a href="https://ebird.org" target="_blank">eBird</a></li><li><a href="#">Contacto</a></li></ul></div>
  </div>
  <div class="footer-bottom">&copy; 2026 Proyecto ORNIS · Ingeniería de Sistemas · Cusco, Perú</div>
</footer>

<a href="https://wa.me/51966441527?text=Hola%20Ornis" class="wa-fab" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i></a>

<!-- MODAL FICHA AVE -->
<div class="modal-overlay" id="modalAve">
  <div class="modal-box">
    <div class="modal-gal">
      <img id="modalImg" src="" alt="Ave"/>
      <div class="modal-nav">
        <button class="modal-nb" id="modalPrev"><i class="fa-solid fa-chevron-left"></i></button>
        <span id="imgCounter"></span>
        <button class="modal-nb" id="modalNext"><i class="fa-solid fa-chevron-right"></i></button>
      </div>
      <button class="modal-close" id="modalClose"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-content">
      <h2 id="modalNombre"></h2>
      <p class="modal-sci" id="modalSci"></p>
      <div class="modal-badges" id="modalBadges"></div>
      <div class="modal-detail">
        <div class="md-row"><span class="md-label">🏛️ Familia</span><span class="md-value" id="modalFamilia"></span></div>
        <div class="md-row"><span class="md-label">🔬 Orden</span><span class="md-value" id="modalOrden"></span></div>
        <div class="md-row"><span class="md-label">⛰️ Altitud</span><span class="md-value" id="modalAltitud"></span></div>
        <div class="md-row"><span class="md-label">📍 Ubicación</span><span class="md-value" id="modalUbicacion"></span></div>
        <div class="md-row"><span class="md-label">📅 Fecha</span><span class="md-value" id="modalFecha"></span></div>
        <div class="md-row"><span class="md-label">🌤️ Clima</span><span class="md-value" id="modalClima"></span></div>
        <div class="md-row"><span class="md-label">🎭 Comportamiento</span><span class="md-value" id="modalComport"></span></div>
        <div class="md-row"><span class="md-label">🔢 Cantidad</span><span class="md-value" id="modalConteo"></span></div>
        <div class="md-row" style="flex-direction:column;gap:4px"><span class="md-label">📝 Notas</span><span class="md-value" id="modalNotas" style="font-size:.84rem;line-height:1.6;color:var(--text-muted)"></span></div>
      </div>
      <a href="index.php#registro" class="btn-submit" style="text-decoration:none;display:flex;align-items:center;gap:8px;justify-content:center;margin-top:18px"><i class="fa-solid fa-plus"></i> Registrar avistamiento</a>
    </div>
  </div>
</div>

<script src="<?= JS_URL ?>"></script>
<script>
(function(){
  const grid=document.getElementById('galeriaGrid');
  const ctr=document.getElementById('countRes');
  let chip='',busq='',hab='',cons='',ord='';

  function filtrar(){
    const cards=grid.querySelectorAll('.gal-card');
    let n=0;
    cards.forEach(c=>{
      const ok=
        (!chip||(c.dataset.hab||'').includes(chip))&&
        (!busq||(c.dataset.txt||'').includes(busq))&&
        (!hab||(c.dataset.hab||'').includes(hab))&&
        (!cons||c.dataset.cons===cons)&&
        (!ord||c.dataset.ord===ord);
      c.style.display=ok?'':'none';
      if(ok)n++;
    });
    if(ctr)ctr.textContent=n;
  }

  document.querySelectorAll('.chip').forEach(c=>c.addEventListener('click',()=>{
    document.querySelectorAll('.chip').forEach(x=>x.classList.remove('active'));
    c.classList.add('active'); chip=c.dataset.f||''; filtrar();
  }));

  document.getElementById('buscarEspecie')?.addEventListener('input',e=>{busq=e.target.value.toLowerCase();filtrar();});
  document.getElementById('btnAplicarFiltros')?.addEventListener('click',()=>{
    hab=document.getElementById('filtroHabitat')?.value||'';
    cons=document.getElementById('filtroConservacion')?.value||'';
    ord=document.getElementById('filtroOrden')?.value||'';
    filtrar();
    document.getElementById('filtrosPanel')?.classList.remove('open');
  });
  document.getElementById('btnLimpiarFiltros')?.addEventListener('click',()=>{
    document.querySelectorAll('#filtrosPanel select').forEach(s=>s.value='');
    hab=cons=ord=''; filtrar();
  });

  document.getElementById('buscarMisFotos')?.addEventListener('input',e=>{
    const q=e.target.value.toLowerCase();
    document.querySelectorAll('.mifoto-i').forEach(el=>{
      el.style.display=(!q||(el.dataset.n||'').includes(q))?'':'none';
    });
  });
})();
</script>
</body>
</html>
