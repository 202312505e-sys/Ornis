<?php
// api_top_lugares.php — Top lugares con más avistamientos de observaciones CSV
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=600');
require_once __DIR__ . '/config/conexion.php';

try {
    $stmt = $pdo->query("
        SELECT
            u.nombre_ubicacion   AS nombre,
            CAST(u.latitud  AS DECIMAL(10,6)) AS lat,
            CAST(u.longitud AS DECIMAL(11,6)) AS lng,
            COUNT(o.id_observacion)            AS total
        FROM observaciones o
        JOIN ubicaciones u ON o.id_ubicacion = u.id_ubicacion
        WHERE u.latitud IS NOT NULL AND u.latitud != 0
        GROUP BY u.id_ubicacion, u.nombre_ubicacion, u.latitud, u.longitud
        ORDER BY total DESC
        LIMIT 15
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // fallback: if empty try registros
    if (empty($rows)) {
        $stmt2 = $pdo->query("
            SELECT
                nombre_lugar AS nombre,
                CAST(AVG(latitud)  AS DECIMAL(10,6)) AS lat,
                CAST(AVG(longitud) AS DECIMAL(11,6)) AS lng,
                COUNT(*) AS total
            FROM registros
            WHERE latitud IS NOT NULL AND latitud != 0
            GROUP BY nombre_lugar
            ORDER BY total DESC
            LIMIT 15
        ");
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode([]);
}
