<?php
declare(strict_types=1);
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';
Auth::requerir('auth.php');
$id_usuario = Auth::id();
$id_lista = (int)($_GET['id'] ?? 0);
if ($id_lista <= 0) { header('Location: listas.php'); exit; }
try {
    $st = $pdo->prepare("DELETE FROM listas_avistamiento WHERE id_lista=? AND id_usuario=?");
    $st->execute([$id_lista, $id_usuario]);
    if ($st->rowCount() > 0) header('Location: listas.php?ok=' . urlencode('Lista eliminada correctamente.'));
    else header('Location: listas.php?err=' . urlencode('Lista no encontrada.'));
} catch (PDOException $e) {
    header('Location: listas.php?err=' . urlencode('Error al eliminar.'));
}
exit;
