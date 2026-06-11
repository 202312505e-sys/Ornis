<?php
declare(strict_types=1);
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/rutas.php';
require_once __DIR__ . '/src/Auth.php';
Auth::iniciarSesion();
if (Auth::verificar()) { header('Location: dashboard.php'); exit; }
$redirect = htmlspecialchars($_GET['redirect'] ?? 'dashboard.php', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>ORNIS — Acceso</title>
  <link rel="stylesheet" href="<?= CSS_URL ?>"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script>(function(){const t=localStorage.getItem('ornis-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>
<a href="index.php" style="position:fixed;top:20px;left:22px;z-index:10;color:rgba(255,255,255,.48);font-size:.83rem;display:flex;align-items:center;gap:6px;font-family:sans-serif;transition:.2s;text-decoration:none;">
  <i class="fa-solid fa-arrow-left"></i> Inicio
</a>

<div class="auth-page">
  <div class="auth-wrap">

    <!-- LADO IZQUIERDO — Momotus palette -->
    <div class="auth-side">
      <div>
        <div class="side-logo-wrap">
          <img src="<?= IMG_URL ?>/ornisLogo.png" alt="ORNIS" style="height:44px;width:auto;"/>
          <span>ORNIS</span>
        </div>
        <p class="side-tag">La plataforma de<br><em>observadores de aves</em><br>de los Andes</p>
        <p class="side-desc">Registra tus avistamientos, sube fotografías y contribuye a la ciencia ciudadana desde Cusco.</p>
        <div class="side-feats">
          <div class="side-feat"><i class="fa-solid fa-binoculars"></i> Registra tus avistamientos</div>
          <div class="side-feat"><i class="fa-solid fa-camera"></i> Sube fotografías propias</div>
          <div class="side-feat"><i class="fa-solid fa-chart-bar"></i> Estadísticas personales</div>
          <div class="side-feat"><i class="fa-solid fa-map-location-dot"></i> Mapa de ubicaciones</div>
          <div class="side-feat"><i class="fa-solid fa-shield-halved"></i> Acceso privado y seguro</div>
        </div>
      </div>
      <p class="side-footer">&copy; 2026 Ornis · Proyecto Web · Cusco, Perú</p>
    </div>

    <!-- LADO DERECHO — Formularios -->
    <div class="auth-form-side">

      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars(urldecode($_GET['error']),ENT_QUOTES,'UTF-8') ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars(urldecode($_GET['success']),ENT_QUOTES,'UTF-8') ?></div>
      <?php endif; ?>

      <!-- LOGIN -->
      <div class="form-pane" id="login-pane">
        <h2>¡Bienvenido de vuelta!</h2>
        <p class="fsub">Ingresa tus credenciales para acceder a tu panel</p>
        <form action="login.php" method="POST">
          <input type="hidden" name="redirect" value="<?= $redirect ?>">
          <div class="input-group">
            <label>Correo Electrónico</label>
            <div class="input-box">
              <i class="fa-regular fa-envelope"></i>
              <input type="email" name="email" required placeholder="tu@correo.com" autocomplete="email"/>
            </div>
          </div>
          <div class="input-group">
            <label>Contraseña</label>
            <div class="input-box">
              <i class="fa-solid fa-lock"></i>
              <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password"/>
            </div>
          </div>
          <button type="submit" class="btn-auth"><i class="fa-solid fa-right-to-bracket"></i> Ingresar a ORNIS</button>
        </form>
        <p class="switch-text">¿Aún no tienes cuenta? <a href="#" id="to-register">Regístrate aquí →</a></p>
      </div>

      <!-- REGISTRO -->
      <div class="form-pane hidden" id="register-pane">
        <h2>Crear cuenta</h2>
        <p class="fsub">Únete a la red de observadores de ORNIS</p>
        <form action="registro.php" method="POST">
          <div class="form-row-2">
            <div class="input-group">
              <label>Nombre</label>
              <div class="input-box"><i class="fa-regular fa-user"></i><input type="text" name="nombre" required placeholder="Juan"/></div>
            </div>
            <div class="input-group">
              <label>Apellido</label>
              <div class="input-box"><i class="fa-regular fa-user"></i><input type="text" name="apellido" required placeholder="Quispe"/></div>
            </div>
          </div>
          <div class="input-group">
            <label>Correo Electrónico</label>
            <div class="input-box"><i class="fa-regular fa-envelope"></i><input type="email" name="email" required placeholder="juan@correo.com" autocomplete="email"/></div>
          </div>
          <div class="input-group">
            <label>Contraseña <span style="color:var(--text-muted);font-weight:400;">(mín. 6 caracteres)</span></label>
            <div class="input-box"><i class="fa-solid fa-lock"></i><input type="password" name="password" required placeholder="••••••••" minlength="6" autocomplete="new-password"/></div>
          </div>
          <button type="submit" class="btn-auth"><i class="fa-solid fa-user-plus"></i> Crear mi cuenta</button>
        </form>
        <p class="switch-text">¿Ya eres miembro? <a href="#" id="to-login">Inicia sesión →</a></p>
      </div>

    </div>
  </div>
</div>
<script src="<?= JS_URL ?>"></script>
</body>
</html>
