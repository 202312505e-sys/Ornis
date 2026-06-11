<?php
declare(strict_types=1);
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';
Auth::requerir('auth.php');

$id_usuario = Auth::id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: listas.php'); exit; }

$edit_id       = (int)($_POST['id_lista'] ?? 0);
$nombre_lista  = mb_substr(trim($_POST['nombre_lista'] ?? ''), 0, 200);
$fecha_lista   = trim($_POST['fecha_lista'] ?? date('Y-m-d'));
$hora_inicio   = trim($_POST['hora_inicio'] ?? '') ?: null;
$duracion_min  = !empty($_POST['duracion_min']) ? (int)$_POST['duracion_min'] : null;
$tipo_protocolo= trim($_POST['tipo_protocolo'] ?? 'libre');
$lugar_nombre  = mb_substr(trim($_POST['lugar_nombre'] ?? ''), 0, 255) ?: null;
$lugar_lat     = !empty($_POST['lugar_lat']) ? (float)$_POST['lugar_lat'] : null;
$lugar_lng     = !empty($_POST['lugar_lng']) ? (float)$_POST['lugar_lng'] : null;
$notas         = mb_substr(trim($_POST['notas'] ?? ''), 0, 2000) ?: null;

$sp_codes   = $_POST['sp_code']   ?? [];
$sp_nombres = $_POST['sp_nombre'] ?? [];
$sp_scis    = $_POST['sp_sci']    ?? [];
$sp_cants   = $_POST['sp_cant']   ?? [];
$sp_notas   = $_POST['sp_notas']  ?? [];

if (!$nombre_lista) {
    header('Location: lista_form.php?err=' . urlencode('El nombre de la lista es obligatorio.')); exit;
}

try {
    $pdo->beginTransaction();

    if ($edit_id > 0) {
        // Verify ownership
        $st = $pdo->prepare("SELECT id_lista FROM listas_avistamiento WHERE id_lista=? AND id_usuario=?");
        $st->execute([$edit_id, $id_usuario]);
        if (!$st->fetch()) { $pdo->rollBack(); header('Location: listas.php?err=' . urlencode('Lista no encontrada.')); exit; }

        $pdo->prepare("UPDATE listas_avistamiento SET nombre_lista=?,fecha_lista=?,hora_inicio=?,duracion_min=?,tipo_protocolo=?,lugar_nombre=?,lugar_lat=?,lugar_lng=?,notas=? WHERE id_lista=? AND id_usuario=?")
            ->execute([$nombre_lista,$fecha_lista,$hora_inicio,$duracion_min,$tipo_protocolo,$lugar_nombre,$lugar_lat,$lugar_lng,$notas,$edit_id,$id_usuario]);
        $pdo->prepare("DELETE FROM lista_especies WHERE id_lista=?")->execute([$edit_id]);
        $id_lista = $edit_id;
    } else {
        $pdo->prepare("INSERT INTO listas_avistamiento (id_usuario,nombre_lista,fecha_lista,hora_inicio,duracion_min,tipo_protocolo,lugar_nombre,lugar_lat,lugar_lng,notas) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$id_usuario,$nombre_lista,$fecha_lista,$hora_inicio,$duracion_min,$tipo_protocolo,$lugar_nombre,$lugar_lat,$lugar_lng,$notas]);
        $id_lista = (int)$pdo->lastInsertId();
    }

    // Insert especies
    $stmt_esp = $pdo->prepare("INSERT INTO lista_especies (id_lista,species_code,nombre_comun,sci_name,cantidad,notas_especie,orden) VALUES (?,?,?,?,?,?,?)");
    foreach ($sp_nombres as $i => $nombre_comun) {
        $nombre_comun = mb_substr(trim($nombre_comun), 0, 200);
        if (!$nombre_comun) continue;
        $stmt_esp->execute([
            $id_lista,
            !empty($sp_codes[$i]) ? trim($sp_codes[$i]) : null,
            $nombre_comun,
            !empty($sp_scis[$i])  ? mb_substr(trim($sp_scis[$i]),0,200) : null,
            max(0, (int)($sp_cants[$i] ?? 1)),
            !empty($sp_notas[$i]) ? mb_substr(trim($sp_notas[$i]),0,500) : null,
            $i + 1
        ]);
    }

    $pdo->commit();
    $msg = $edit_id > 0 ? 'Lista actualizada correctamente.' : 'Lista creada con éxito.';
    header('Location: lista_ver.php?id=' . $id_lista . '&ok=' . urlencode($msg));
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[ORNIS-LISTA] ' . $e->getMessage());
    header('Location: lista_form.php?err=' . urlencode('Error al guardar. Inténtalo de nuevo.'));
}
exit;
