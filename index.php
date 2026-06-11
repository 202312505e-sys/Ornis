<?php
declare(strict_types=1);
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/rutas.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';

Auth::iniciarSesion();
$logueado = Auth::verificar();
$nombre   = $logueado ? Auth::nombre() : '';

$stats = ['especies'=>348,'avistamientos'=>1240,'observadores'=>86];
try {
  $r = $pdo->query("SELECT COUNT(DISTINCT nombre_comun) AS e FROM registros");
  $v = $r->fetch(); if ($v && (int)$v['e'] > 10) $stats['especies']=(int)$v['e'];
} catch(\PDOException){}

function e(mixed $v):string{ return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Proyecto ORNIS — Avistamiento de Aves en Cusco</title>
  <link rel="stylesheet" href="<?= CSS_URL ?>"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script>(function(){const t=localStorage.getItem('ornis-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>

<!-- ═══ NAVBAR ═══════════════════════════════════════════════ -->
<nav class="navbar" id="navbar">
  <a href="index.php" class="nav-logo">
    <img src="<?= IMG_URL ?>/ornisLogo.png" alt="ORNIS"/>
    <span class="nav-brand">ORNIS</span>
  </a>
  <ul class="nav-links">
    <li><a href="index.php" class="active-link">Inicio</a></li>
    <li><a href="galeria.php">Galería</a></li>
    <li><a href="registrar.php">Registrar</a></li>
    <?php if($logueado):?>
      <li><a href="listas.php">Mis Listas</a></li>
      <li><a href="dashboard.php" class="nav-accent">Mi Panel</a></li>
      <li><a href="logout.php" class="nav-danger">Salir</a></li>
    <?php else:?>
      <li><a href="#footer">Contacto</a></li>
      <li><a href="auth.php" style="color:var(--mo-turq)">Ingresar</a></li>
    <?php endif;?>
  </ul>
  <button class="theme-toggle" aria-label="Cambiar tema"><span class="ico">🌙</span><span class="lbl">Oscuro</span></button>
  <?php if($logueado):?>
    <a href="registrar.php" class="nav-cta">+ Registrar</a>
  <?php else:?>
    <a href="auth.php" class="nav-cta">Ingresar →</a>
  <?php endif;?>
</nav>

<!-- ═══ HERO ══════════════════════════════════════════════════ -->
<section class="hero" id="inicio">
  <div class="hero-bg">
    <img class="hero-slide active" src="https://pbs.twimg.com/media/F1--CiGWYAQIr55.jpg" alt=""/>
    <img class="hero-slide" src="https://cdn.download.ams.birds.cornell.edu/api/v2/asset/249551571/900" alt=""/>
    <img class="hero-slide" src="https://cdn.download.ams.birds.cornell.edu/api/v2/asset/658233909/900" alt=""/>
    <img class="hero-slide" src="https://cdn.download.ams.birds.cornell.edu/api/v2/asset/495775691/1200" alt=""/>
    <img class="hero-slide" src="https://cdn.download.ams.birds.cornell.edu/api/v1/asset/56523561/900" alt=""/>
    <div class="hero-overlay"></div>
  </div>
  <div class="hero-content">
    <div class="hero-eyebrow">📍 Cusco, Perú · Andes del Sur</div>
    <h1 class="hero-title">Descubre las aves<br/><em>de los Andes</em></h1>
    <p class="hero-sub">Registra tus avistamientos, explora la galería y contribuye<br/>a la ciencia ciudadana en el corazón del Perú.</p>
    <div class="hero-btns">
      <button class="btn-primary" onclick="document.getElementById('registro').scrollIntoView({behavior:'smooth'})">
        <i class="fa-solid fa-plus"></i> Registrar avistamiento
      </button>
      <a href="galeria.php" class="btn-ghost">Ver galería →</a>
    </div>
    <div class="hero-stats">
      <div class="stat"><span class="stat-n"><?= number_format($stats['especies']) ?></span><span class="stat-l">Especies</span></div>
      <div class="stat"><span class="stat-n"><?= number_format($stats['avistamientos']) ?></span><span class="stat-l">Avistamientos</span></div>
      <div class="stat"><span class="stat-n"><?= number_format($stats['observadores']) ?></span><span class="stat-l">Observadores</span></div>
    </div>
  </div>
  <div class="hero-scroll">↓ Desliza</div>
</section>

<!-- ═══ ABOUT ═════════════════════════════════════════════════ -->
<section style="background:var(--bg-surface);padding:88px 0">
  <div class="about-grid">
    <div class="fade-in">
      <h2>¿Qué es Proyecto ORNIS?</h2>
      <p>ORNIS es una plataforma para que aficionados y científicos puedan <strong style="color:var(--mo-corona)">registrar y documentar</strong> avistamientos de aves en la región de Cusco y sus alrededores.</p>
      <ul class="about-list">
        <li>🌿 Contribuye a la conservación de especies</li>
        <li>🗺️ Identifica zonas de biodiversidad</li>
        <li>🔬 Crea un historial científico accesible</li>
        <li>🤝 Conecta a observadores de toda la región</li>
      </ul>
    </div>
    <div class="fade-in">
      <h3 style="font-family:var(--font-display);font-size:1.5rem;margin-bottom:20px;color:var(--text-primary)">¿Cómo registrar?</h3>
      <ol class="steps-list">
        <li><span class="step-n">01</span> Observa el ave y anota sus características</li>
        <li><span class="step-n">02</span> Toma una fotografía si es posible</li>
        <li><span class="step-n">03</span> Anota la ubicación y hora exactas</li>
        <li><span class="step-n">04</span> Completa y envía el formulario</li>
      </ol>
    </div>
  </div>
</section>

<!-- ═══ AVES DESTACADAS ════════════════════════════════════════ -->
<section style="background:var(--bg-muted)">
  <div class="section-header fade-in"><h2>Aves Destacadas</h2><p>Algunas de las especies más representativas de la región</p></div>
  <div class="cards-grid">
    <?php
    $cards=[
      ['Gallito de las Rocas','Rupicola peruvianus','Ave Nacional','https://cdn.download.ams.birds.cornell.edu/api/v1/asset/56869081/900'],
      ['Cóndor Andino','Vultur gryphus','Especie emblema','https://cdn.download.ams.birds.cornell.edu/api/v1/asset/115347711/2400'],
      ['Motmot Andino','Momotus aequatorialis','Cola Raqueta','https://cdn.download.ams.birds.cornell.edu/api/v1/asset/56523561/900'],
      ['Colibrí Gigante','Patagona gigas','El más grande','https://cdn.download.ams.birds.cornell.edu/api/v2/asset/601413861/900'],
      ['Tangara del Paraíso','Tangara chilensis','Colores vibrantes','https://cdn.download.ams.birds.cornell.edu/api/v2/asset/322197901/900'],
      ['Pato de Torrente','Merganetta armata','Ríos andinos','https://cdn.download.ams.birds.cornell.edu/api/v1/asset/115695161/900'],
    ];
    foreach($cards as $c):?>
    <div class="ave-card fade-in">
      <img src="<?= e($c[3]) ?>" alt="<?= e($c[0]) ?>" loading="lazy"/>
      <div class="ave-card-body">
        <span class="ave-tag"><?= e($c[2]) ?></span>
        <h3><?= e($c[0]) ?></h3>
        <p><?= e($c[1]) ?></p>
      </div>
    </div>
    <?php endforeach;?>
  </div>
  <div style="text-align:center;margin-top:34px">
    <a href="galeria.php" style="display:inline-block;padding:13px 32px;border:1.5px solid var(--accent);color:var(--accent);border-radius:999px;font-weight:600;transition:.2s" onmouseover="this.style.background='var(--accent)';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='var(--accent)'">Ver galería completa →</a>
  </div>
</section>

<!-- ═══ MAPA ════════════════════════════════════════════════ -->
<section class="mapa-section" id="mapa">
  <div class="section-header dark fade-in">
    <h2>Zonas de Avistamiento</h2>
    <p>Top lugares con más registros según datos eBird · Haz clic para navegar</p>
  </div>
  <div class="mapa-layout-wrap">
    <!-- MAP -->
    <div class="mapa-col-map">
      <div id="mapaCusco"></div>
      <div class="mapa-puntos" id="mapaPuntos">
        <!-- filled by JS from static puntos -->
      </div>
    </div>
    <!-- SIDEBAR: Top lugares desde BD -->
    <div class="mapa-col-sidebar">
      <div class="top-lugares-header">
        <i class="fa-solid fa-ranking-star"></i>
        <span>Top Lugares</span>
        <small>por avistamientos</small>
      </div>
      <div class="top-lugares-list" id="topLugaresList">
        <div class="tl-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ FORMULARIO REGISTRO ════════════════════════════════════ -->
<section class="registro-section" id="registro">
  <div class="section-header fade-in">
    <h2>📋 Registrar un Avistamiento</h2>
    <p>Completa los datos para contribuir al banco de datos de ORNIS</p>
  </div>

  <?php if(isset($_GET['reg_success'])):?>
    <div class="alert alert-success" style="max-width:760px;margin:0 auto 20px"><i class="fa-solid fa-circle-check"></i> ¡Avistamiento guardado con éxito!</div>
  <?php elseif(isset($_GET['reg_error'])):?>
    <div class="alert alert-error" style="max-width:760px;margin:0 auto 20px"><i class="fa-solid fa-circle-exclamation"></i> <?= e(urldecode($_GET['reg_error'])) ?></div>
  <?php endif;?>

  <div class="registro-grid">
    <!-- FORMULARIO -->
    <div>
      <div class="form-card">
        <?php if($logueado):?>
        <form action="guardar_avistamiento.php" method="POST" enctype="multipart/form-data">
        <?php else:?>
        <form onsubmit="event.preventDefault();window.location='auth.php';">
        <?php endif;?>

          <span class="form-section-label">🦜 Especie</span>
          <div class="input-group">
            <label for="nombre_ave_input">Nombre del ave</label>
            <div class="ac-wrap">
              <input type="text" id="nombre_ave_input" name="nombre_comun" required
                     placeholder="Ej: Gallito de las Rocas" autocomplete="off"/>
              <input type="hidden" id="species_code_input" name="species_code"/>
              <input type="hidden" id="sci_name_input" name="nombre_cientifico"/>
              <ul class="ac-list" id="autocomplete_list" style="display:none"></ul>
            </div>
          </div>

          <div class="form-row">
            <div class="input-group">
              <label>Cantidad observada</label>
              <input type="number" name="cantidad" min="1" max="999" value="1" required/>
            </div>
            <div class="input-group">
              <label>Fecha</label>
              <input type="date" name="fecha_avistamiento" required value="<?= date('Y-m-d') ?>"/>
            </div>
          </div>

          <div class="form-row">
            <div class="input-group">
              <label>Clima</label>
              <select name="clima">
                <option value="">— Selecciona —</option>
                <option value="soleado">☀️ Soleado</option>
                <option value="nublado">☁️ Nublado</option>
                <option value="lluvioso">🌧️ Lluvioso</option>
                <option value="neblina">🌫️ Neblina</option>
              </select>
            </div>
            <div class="input-group">
              <label>Comportamiento</label>
              <select name="comportamiento">
                <option value="">— Selecciona —</option>
                <option value="volando">Volando</option>
                <option value="alimentandose">Alimentándose</option>
                <option value="anidando">Anidando</option>
                <option value="en_reposo">En reposo</option>
                <option value="cantando">Cantando</option>
              </select>
            </div>
          </div>

          <div class="input-group">
            <label>Fotografía del ave</label>
            <div class="upload-zone" id="uploadZone">
              <input type="file" id="foto_input" name="foto_ave" accept="image/*" style="display:none"/>
              <div style="font-size:2rem;margin-bottom:8px">📷</div>
              <p>Arrastra una imagen o <strong>haz clic</strong></p>
              <p style="font-size:.75rem;color:var(--text-muted);margin-top:4px">JPG, PNG, WEBP · Máx 10 MB</p>
            </div>
            <div class="upload-preview" id="uploadPreview"><img id="uploadPreviewImg" alt=""/></div>
          </div>

          <span class="form-section-label">📍 Ubicación</span>

          <button type="button" class="btn-gps" id="btnGPS">
            <i class="fa-solid fa-location-crosshairs"></i> Usar mi ubicación GPS
          </button>

          <div class="input-group">
            <label for="reg_ubicacion_texto">Nombre del lugar</label>
            <input type="text" id="reg_ubicacion_texto" name="nombre_lugar" required placeholder="Ej: Valle Sagrado, km 45"/>
          </div>
          <div class="form-row">
            <div class="input-group">
              <label>Latitud</label>
              <input type="number" step="any" id="reg_lat" name="latitud" placeholder="-13.5226" required/>
            </div>
            <div class="input-group">
              <label>Longitud</label>
              <input type="number" step="any" id="reg_lng" name="longitud" placeholder="-71.9673" required/>
            </div>
          </div>

          <span class="form-section-label">📝 Notas</span>
          <div class="input-group">
            <textarea name="notas" rows="3" placeholder="Describe el contexto del avistamiento…"></textarea>
          </div>

          <?php if(!$logueado):?>
            <span class="form-section-label">👤 Datos del Observador</span>
            <div class="form-row">
              <div class="input-group"><label>Nombre</label><input type="text" placeholder="Juan Quispe"/></div>
              <div class="input-group"><label>Correo</label><input type="email" placeholder="juan@correo.com"/></div>
            </div>
          <?php endif;?>

          <button type="submit" class="btn-submit">
            <?= $logueado
              ? '<i class="fa-solid fa-cloud-arrow-up"></i> Guardar en la Base de Datos'
              : '<i class="fa-solid fa-lock"></i> Inicia sesión para guardar' ?>
          </button>
        </form>
      </div>
    </div>

    <!-- MAPA REGISTRO -->
    <div class="mapa-registro">
      <h4>📍 Selecciona la ubicación en el mapa</h4>
      <div id="mapaRegistro"></div>
      <div class="coords-box" id="coordsDisplay">Haz clic en el mapa o usa el botón GPS</div>
      <p class="instruccion">💡 El GPS autocompleta nombre del lugar y coordenadas</p>
    </div>
  </div>
</section>

<!-- ═══ FOOTER ════════════════════════════════════════════════ -->
<footer id="footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="logo-w"><img src="<?= IMG_URL ?>/ornisLogo.png" alt="ORNIS"/><span class="fn">ORNIS</span></div>
      <p>Plataforma de ciencia ciudadana para observación de aves en los Andes del sur del Perú.</p>
    </div>
    <div>
      <h4>Plataforma</h4>
      <ul>
        <li><a href="index.php">Inicio</a></li>
        <li><a href="galeria.php">Galería</a></li>
        <li><a href="#registro">Registrar</a></li>
        <li><a href="auth.php">Panel</a></li>
      </ul>
    </div>
    <div>
      <h4>Proyecto</h4>
      <ul>
        <li><a href="#">Universidad Andina del Cusco</a></li>
        <li><a href="https://ebird.org" target="_blank">eBird</a></li>
        <li><a href="#">Contacto</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">&copy; 2026 Proyecto ORNIS · Ingeniería de Sistemas · Cusco, Perú</div>
</footer>

<a href="https://wa.me/51966441527?text=Hola%20Ornis" class="wa-fab" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i></a>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= JS_URL ?>"></script>
</body>
</html>
