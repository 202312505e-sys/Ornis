<?php
// ============================================================
//  guardar_avistamiento.php — Procesa el formulario de registro
//  POST desde index.php (solo usuarios logueados)
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/rutas.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/RegistroModel.php';

// Verificar sesión activa
Auth::requerir('auth.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#registro');
    exit;
}

$modelo    = new RegistroModel($pdo);
$resultado = $modelo->guardar(
    Auth::id(),
    $_POST,
    $_FILES['foto_ave'] ?? null
);

if ($resultado['ok']) {
    header('Location: dashboard.php?ok=' . urlencode('¡Avistamiento guardado con éxito!'));
} else {
    header('Location: index.php?reg_error=' . urlencode($resultado['msg']) . '#registro');
}
exit;
