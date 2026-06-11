<?php
// Navbar partial — usado en todas las páginas de ORNIS
// Requiere: Auth inicializado, CSS_URL, IMG_URL definidos
$_logueado   = Auth::verificar();
$_nombre_nav = $_logueado ? Auth::nombre() : '';
?>
<nav class="navbar" id="navbar">
  <a href="<?= BASE_URL ?>/index.php" class="nav-logo">
    <img src="<?= IMG_URL ?>/ornisLogo.png" alt="ORNIS"/>
    <span class="nav-brand">ORNIS</span>
  </a>
  <ul class="nav-links">
    <li><a href="<?= BASE_URL ?>/index.php">Inicio</a></li>
    <li><a href="<?= BASE_URL ?>/galeria.php">Galería</a></li>
    <?php if($_logueado): ?>
      <li><a href="<?= BASE_URL ?>/registrar.php">Registrar</a></li>
      <li><a href="<?= BASE_URL ?>/listas.php">Mis Listas</a></li>
      <li><a href="<?= BASE_URL ?>/dashboard.php" class="nav-accent">Mi Panel</a></li>
      <li><a href="<?= BASE_URL ?>/logout.php" class="nav-danger">Salir</a></li>
    <?php else: ?>
      <li><a href="<?= BASE_URL ?>/auth.php" style="color:var(--mo-turq)">Ingresar</a></li>
    <?php endif; ?>
  </ul>
  <button class="theme-toggle" aria-label="Cambiar tema"><span class="ico">🌙</span><span class="lbl">Oscuro</span></button>
  <?php if($_logueado): ?>
    <a href="<?= BASE_URL ?>/registrar.php" class="nav-cta">+ Registrar</a>
  <?php else: ?>
    <a href="<?= BASE_URL ?>/auth.php" class="nav-cta">Ingresar →</a>
  <?php endif; ?>
</nav>
