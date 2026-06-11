<?php
// ============================================================
//  login.php — Procesa el formulario de login
//  CORRECCIÓN: incluye conexion.php (necesario para new Auth($pdo))
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';

Auth::iniciarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: auth.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

$auth      = new Auth($pdo);
$resultado = $auth->login($email, $password);

if ($resultado['ok']) {
    $redirect = trim($_POST['redirect'] ?? '');
    // Validar que el redirect sea una ruta relativa segura
    if (!$redirect || !preg_match('/^[\w\-\/\.]+\.php$/', $redirect)) {
        $redirect = 'dashboard.php';
    }
    header("Location: {$redirect}");
    exit;
}

header('Location: auth.php?error=' . urlencode($resultado['msg']));
exit;
