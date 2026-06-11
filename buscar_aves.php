<?php
// ============================================================
//  buscar_aves.php — API JSON autocompletado taxonómico
//  GET ?q=texto       → nombre común o científico (mín. 2 chars)
//  GET ?q=texto&all=1 → incluye híbridos y subespecies
//  CORRECCIÓN: require usa __DIR__ para rutas absolutas
// ============================================================
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/config/conexion.php';

$q   = trim($_GET['q'] ?? '');
$all = !empty($_GET['all']);

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// Sanitizar: permitir solo caracteres válidos en nombres de aves
$q = preg_replace('/[^\w\s\-\.áéíóúüñÁÉÍÓÚÜÑ]/u', '', $q);

// Filtro de categoría: por defecto solo species e issf
$catFilter = $all ? '' : "AND category IN ('species','issf')";

$stmt = $pdo->prepare("
    SELECT  species_code,
            primary_com_name,
            sci_name,
            family,
            e_order,
            category
    FROM    especies_taxonomia
    WHERE   (primary_com_name LIKE :q OR sci_name LIKE :q)
            {$catFilter}
    ORDER BY
            CASE WHEN primary_com_name LIKE :qs THEN 0 ELSE 1 END,
            primary_com_name
    LIMIT   12
");

$termino = "%{$q}%";
$inicio  = "{$q}%";

$stmt->bindValue(':q',  $termino, PDO::PARAM_STR);
$stmt->bindValue(':qs', $inicio,  PDO::PARAM_STR);
$stmt->execute();

echo json_encode(
    $stmt->fetchAll(),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
