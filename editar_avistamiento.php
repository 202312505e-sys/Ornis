<?php
// ============================================================
//  editar_avistamiento.php — Procesa la edición de avistamiento
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/rutas.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/RegistroModel.php';

Auth::requerir('auth.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$id_registro = (int)($_POST['id_registro'] ?? 0);
$id_usuario  = Auth::id();

if ($id_registro <= 0) {
    header('Location: dashboard.php?err=' . urlencode('ID inválido.'));
    exit;
}

$modelo    = new RegistroModel($pdo);
$resultado = $modelo->editar(
    $id_registro,
    $id_usuario,
    $_POST,
    $_FILES['foto_ave'] ?? null
);

if ($resultado['ok']) {
    header('Location: dashboard.php?ok=' . urlencode('Avistamiento actualizado correctamente.'));
} else {
    header('Location: registrar.php?edit=' . $id_registro . '&err=' . urlencode($resultado['msg']));
}
exit;
