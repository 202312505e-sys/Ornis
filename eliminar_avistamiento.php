<?php
// ============================================================
//  eliminar_avistamiento.php — Elimina un avistamiento propio
//  GET: id (int) — el modelo verifica que pertenezca al usuario
// ============================================================

declare(strict_types=1);

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/RegistroModel.php';

// Verificar sesión
Auth::requerir('auth.php');

$id_registro = (int) ($_GET['id'] ?? 0);
$id_usuario  = Auth::id();

if ($id_registro <= 0) {
    header('Location: dashboard.php?err=' . urlencode('ID de registro inválido.'));
    exit;
}

$modelo    = new RegistroModel($pdo);
$eliminado = $modelo->eliminar($id_registro, $id_usuario);

if ($eliminado) {
    header('Location: dashboard.php?ok=' . urlencode('Avistamiento eliminado correctamente.'));
} else {
    header('Location: dashboard.php?err=' . urlencode('No se pudo eliminar el avistamiento.'));
}
exit;
