<?php
// ============================================================
//  registro.php — Procesa el formulario de registro
//  CORRECCIÓN: incluye conexion.php
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: auth.php#registro');
    exit;
}

$auth      = new Auth($pdo);
$resultado = $auth->registrar(
    $_POST['nombre']   ?? '',
    $_POST['apellido'] ?? '',
    $_POST['email']    ?? '',
    $_POST['password'] ?? ''
);

if ($resultado['ok']) {
    header('Location: auth.php?success=' . urlencode('¡Cuenta creada! Inicia sesión ahora.'));
    exit;
}

header('Location: auth.php?error=' . urlencode($resultado['msg']) . '#registro');
exit;
