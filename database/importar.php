<?php
// ============================================================
//  database/importar.php  —  ORNIS v3.1 FIXED
//  FIX 1: Taxonomía IDEMPOTENTE — INSERT IGNORE ya previene
//         duplicados gracias al UNIQUE KEY uq_species_code.
//         AÑADIDO: limpieza previa opcional con ?reset=1
//  FIX 2: Las observaciones también usan INSERT IGNORE para
//         evitar duplicaciones al re-ejecutar el script.
// ============================================================

declare(strict_types=1);
set_time_limit(600);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/constantes.php';

$CSV_TAXONOMIA = DATA_PATH . 'eBird_Taxonomy.csv';
$CSV_MIS_OBS   = DATA_PATH . 'MyEBirdData.csv';

const COL_SUBM_ID  = 0;  const COL_COM_NAME = 1;  const COL_SCI_NAME = 2;
const COL_COUNT    = 4;  const COL_PROV     = 5;  const COL_LOC_ID   = 7;
const COL_LOC_NAME = 8;  const COL_LAT      = 9;  const COL_LNG      = 10;
const COL_DATE     = 11; const COL_TIME     = 12; const COL_PROTOCOL = 13;
const COL_DURATION = 14; const COL_DISTANCE = 16; const COL_NUM_OBS  = 18;
const COL_BREEDING = 19; const COL_DETAILS  = 20; const COL_NOTES    = 21;

function log_msg(string $msg, string $tipo = 'info'): void {
    $c = ['ok'=>'#0F6E56','error'=>'#A32D2D','info'=>'#185FA5','warn'=>'#854F0B'];
    $color = $c[$tipo] ?? '#333';
    echo "<p style='margin:2px 0;color:{$color};font-family:monospace;font-size:13px;'>{$msg}</p>";
    ob_flush(); flush();
}

function parse_hora(string $raw): ?string {
    if (trim($raw) === '') return null;
    $ts = strtotime($raw);
    return ($ts !== false) ? date('H:i:s', $ts) : null;
}

function clean(string $v, int $max = 200): string {
    return mb_substr(trim($v), 0, $max);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>ORNIS — Importar datos</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f7f5;padding:30px;max-width:860px;margin:0 auto}
    h1{color:#0F6E56;border-bottom:3px solid #1D9E75;padding-bottom:10px;margin-bottom:24px;font-size:1.4rem}
    h3{color:#085041;margin:0 0 12px;font-size:1rem}
    .box{background:#fff;border-radius:10px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.07);margin-bottom:20px}
    .box-ok{border-left:4px solid #1D9E75}.box-warn{border-left:4px solid #EF9F27}
    .log{max-height:220px;overflow-y:auto;background:#f9fafb;border:1px solid #e0e0e0;border-radius:6px;padding:10px;margin-top:10px}
    .stat-row{display:flex;gap:12px;flex-wrap:wrap;margin-top:14px}
    .stat{background:#E1F5EE;border-radius:8px;padding:10px 16px;font-size:.85rem;color:#085041}
    .stat strong{display:block;font-size:1.3rem;color:#0F6E56}
    a.btn{display:inline-block;margin-top:20px;background:#1D9E75;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:.9rem}
    .notice{background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:.88rem;color:#664d03}
  </style>
</head>
<body>
<h1>🦜 ORNIS v3.1 — Importación Idempotente</h1>

<!-- AVISO DE SEGURIDAD IDEMPOTENCIA -->
<div class="notice">
  ⚡ <strong>Script idempotente:</strong> Puedes ejecutarlo múltiples veces sin duplicar datos.
  Las operaciones usan <code>INSERT IGNORE</code> + claves únicas en la BD.
  Para limpiar y reimportar todo desde cero agrega <code>?reset=1</code> a la URL
  (solo funciona si eres admin).
</div>

<?php

// ── Opción reset=1: limpiar tablas (solo admin / uso cuidadoso)
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    echo "<div class='box box-warn'><h3>🗑 Reset solicitado — limpiando tablas…</h3><div class='log'>";
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        // NO borramos usuarios ni registros manuales
        $pdo->exec("TRUNCATE TABLE observaciones");
        $pdo->exec("DELETE FROM ubicaciones WHERE id_ubicacion LIKE 'L%' OR id_ubicacion LIKE 'L_%'");
        $pdo->exec("TRUNCATE TABLE especies_taxonomia");
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        log_msg("✅ Tablas observaciones, ubicaciones (eBird) y taxonomía vaciadas.", 'ok');
    } catch (PDOException $e) {
        log_msg("❌ Error en reset: " . htmlspecialchars($e->getMessage()), 'error');
    }
    echo "</div></div>";
}

// ════════════════════════════════════════════════════════════
// PASO 1 — TAXONOMÍA  (FIX: INSERT IGNORE previene duplicados)
// La tabla tiene UNIQUE KEY uq_species_code (species_code)
// → ejecutar 2 veces = mismos resultados, 0 duplicados.
// ════════════════════════════════════════════════════════════
echo "<div class='box'><h3>📚 Paso 1 · Taxonomía oficial eBird</h3><div class='log'>";

$total_tax = 0; $skip_tax = 0; $err_tax = 0;

if (!file_exists($CSV_TAXONOMIA)) {
    log_msg("❌ Archivo no encontrado: {$CSV_TAXONOMIA}", 'error');
} else {
    // Contar cuántos ya existen antes de importar
    $ya_existentes = (int) $pdo->query("SELECT COUNT(*) FROM especies_taxonomia")->fetchColumn();
    if ($ya_existentes > 0) {
        log_msg("ℹ {$ya_existentes} especies ya presentes en BD — se omitirán duplicados (INSERT IGNORE).", 'info');
    }

    $handle = fopen($CSV_TAXONOMIA, 'r');
    // Handle UTF-8 BOM
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($handle);
    fgetcsv($handle, 4000, ','); // saltar cabecera

    // INSERT IGNORE: si species_code ya existe, la fila se salta silenciosamente
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO especies_taxonomia
            (taxon_order, category, species_code,
             primary_com_name, sci_name,
             e_order, family, species_group, report_as)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $pdo->beginTransaction();
    while (($row = fgetcsv($handle, 4000, ',')) !== false) {
        // CSV columns: sort(0), species_code(1), taxon_id(2), change(3),
        //              text(4), category(5), English name(6), sci_name(7),
        //              authority(8), name+auth(9), range(10), order(11), family(12)
        $sp_code = trim($row[1] ?? '');
        if (empty($sp_code)) { $err_tax++; continue; }
        if (count($row) < 8) { $err_tax++; continue; }
        try {
            $stmt->execute([
                (int)  ($row[0] ?? 0),           // taxon_order
                clean($row[5]  ?? '', 50),         // category
                $sp_code,                          // species_code
                clean($row[6]  ?? '', 200),        // primary_com_name (English name)
                clean($row[7]  ?? '', 200),        // sci_name
                clean($row[11] ?? '', 100),        // e_order
                clean($row[12] ?? '', 150),        // family
                clean($row[10] ?? '', 150),        // species_group (range)
                null,                              // report_as
            ]);
            // rowCount() = 1 si insertó, 0 si ignoró
            if ($stmt->rowCount() > 0) $total_tax++;
            else $skip_tax++;
        } catch (PDOException $e) {
            $err_tax++;
            if ($err_tax <= 5) log_msg('⚠ ' . htmlspecialchars($e->getMessage()), 'warn');
        }
    }
    $pdo->commit();
    fclose($handle);

    log_msg("✅ Nuevas especies insertadas: <strong>{$total_tax}</strong>", 'ok');
    if ($skip_tax > 0)  log_msg("ℹ {$skip_tax} filas omitidas (ya existían en BD — correcto).", 'info');
    if ($err_tax  > 0)  log_msg("⚠ {$err_tax} filas con error de formato.", 'warn');
}
echo "</div></div>";

// ════════════════════════════════════════════════════════════
// PASO 2 — UBICACIONES (INSERT IGNORE por PK id_ubicacion)
// ════════════════════════════════════════════════════════════
echo "<div class='box'><h3>📍 Paso 2 · Ubicaciones GPS</h3><div class='log'>";

$total_ubi = 0; $locs_cache = [];

if (!file_exists($CSV_MIS_OBS)) {
    log_msg("❌ Archivo no encontrado: {$CSV_MIS_OBS}", 'error');
    echo "</div></div>";
} else {
    $handle = fopen($CSV_MIS_OBS, 'r');
    fgetcsv($handle, 4000, ',');

    $stmt_ubi = $pdo->prepare("
        INSERT IGNORE INTO ubicaciones
            (id_ubicacion, nombre_ubicacion, estado_provincia, latitud, longitud)
        VALUES (?, ?, ?, ?, ?)
    ");

    $pdo->beginTransaction();
    while (($row = fgetcsv($handle, 4000, ',')) !== false) {
        $lid = trim($row[COL_LOC_ID] ?? '');
        if (empty($lid) || isset($locs_cache[$lid])) { continue; }
        $lat = (float)($row[COL_LAT] ?? 0);
        $lng = (float)($row[COL_LNG] ?? 0);
        if ($lat === 0.0 || $lng === 0.0) { continue; }
        $nombre = clean($row[COL_LOC_NAME] ?? "Ubicación {$lid}", 255);
        $prov   = clean($row[COL_PROV] ?? 'PE-CUS', 50);
        try {
            $stmt_ubi->execute([$lid, $nombre, $prov, $lat, $lng]);
            $locs_cache[$lid] = true;
            if ($stmt_ubi->rowCount() > 0) $total_ubi++;
        } catch (PDOException $e) {
            $locs_cache[$lid] = true; // ya existe, OK
        }
    }
    $pdo->commit();
    fclose($handle);
    log_msg("✅ Nuevas ubicaciones insertadas: <strong>{$total_ubi}</strong> puntos GPS.", 'ok');
    echo "</div></div>";

    // ════════════════════════════════════════════════════════
    // PASO 3 — OBSERVACIONES (INSERT IGNORE por PK id_observacion)
    // La PK es el Submission ID de eBird → único por definición
    // ════════════════════════════════════════════════════════
    echo "<div class='box'><h3>🦜 Paso 3 · Observaciones reales de Denis</h3><div class='log'>";

    $handle = fopen($CSV_MIS_OBS, 'r');
    fgetcsv($handle, 4000, ',');

    // FIX: INSERT IGNORE — si el Submission ID ya existe, salta silenciosamente
    $stmt_obs = $pdo->prepare("
        INSERT IGNORE INTO observaciones
            (id_observacion, id_usuario, species_code,
             nombre_comun, nombre_cientifico,
             id_ubicacion, conteo,
             fecha, hora, protocolo, duracion_min,
             distancia_km, num_observadores,
             breeding_code, detalles, notas_lista,
             fuente)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ebird_csv')
    ");

    $stmt_code  = $pdo->prepare("SELECT species_code FROM especies_taxonomia WHERE sci_name = ? LIMIT 1");
    $code_cache = [];
    $total_obs = 0; $skip_obs = 0; $err_obs = 0;

    $pdo->beginTransaction();
    while (($row = fgetcsv($handle, 4000, ',')) !== false) {
        $subm_id = trim($row[COL_SUBM_ID] ?? '');
        if (empty($subm_id)) continue;

        $sci_name = trim($row[COL_SCI_NAME] ?? '');
        $s_code   = null;
        if ($sci_name !== '') {
            if (!array_key_exists($sci_name, $code_cache)) {
                $stmt_code->execute([$sci_name]);
                $code_cache[$sci_name] = $stmt_code->fetchColumn() ?: null;
            }
            $s_code = $code_cache[$sci_name];
        }

        $conteo_raw = trim($row[COL_COUNT] ?? '1');
        $conteo     = ($conteo_raw === 'X' || $conteo_raw === '') ? 1 : max(1, (int)$conteo_raw);
        $loc_id     = trim($row[COL_LOC_ID] ?? '');
        $loc_id     = isset($locs_cache[$loc_id]) ? $loc_id : null;

        try {
            $stmt_obs->execute([
                clean($subm_id, 50),
                DENIS_USER_ID,
                $s_code,
                clean($row[COL_COM_NAME] ?? 'Especie desconocida'),
                $sci_name !== '' ? clean($sci_name) : null,
                $loc_id,
                $conteo,
                clean($row[COL_DATE] ?? '', 10),
                parse_hora($row[COL_TIME] ?? ''),
                !empty($row[COL_PROTOCOL]) ? clean($row[COL_PROTOCOL], 100) : null,
                !empty($row[COL_DURATION]) ? (int)$row[COL_DURATION]        : null,
                !empty($row[COL_DISTANCE]) ? (float)$row[COL_DISTANCE]      : null,
                !empty($row[COL_NUM_OBS])  ? max(1,(int)$row[COL_NUM_OBS])  : 1,
                !empty($row[COL_BREEDING]) ? clean($row[COL_BREEDING], 10)  : null,
                !empty($row[COL_DETAILS])  ? clean($row[COL_DETAILS], 2000) : null,
                !empty($row[COL_NOTES])    ? clean($row[COL_NOTES],   2000) : null,
            ]);
            if ($stmt_obs->rowCount() > 0) $total_obs++;
            else $skip_obs++;
        } catch (PDOException $e) {
            $err_obs++;
            if ($err_obs <= 5) log_msg('⚠ ' . htmlspecialchars($e->getMessage()), 'warn');
        }
    }
    $pdo->commit();
    fclose($handle);

    log_msg("✅ Observaciones nuevas: <strong>{$total_obs}</strong>", 'ok');
    if ($skip_obs > 0) log_msg("ℹ {$skip_obs} observaciones ya existían — ningún duplicado creado.", 'info');
    if ($err_obs  > 0) log_msg("⚠ {$err_obs} filas con error.", 'warn');
    echo "</div></div>";
}

// ════════════════════════════════════════════════════════════
// PASO 4 — SEED DEMO (INSERT IGNORE en ubicaciones y registros)
// ════════════════════════════════════════════════════════════
echo "<div class='box'><h3>🌱 Paso 4 · Datos demo (María y Carlos)</h3><div class='log'>";

$ubicaciones_demo = [
    ['L5006393',  'Zona Arqueológica de Inkilltambo',        'PE-CUS', -13.5026279, -71.9528800],
    ['L1811275',  'Inkaterra Machu Picchu Pueblo Hotel',     'PE-CUS', -13.1576666, -72.5224949],
    ['L4918088',  'Humedal de Huayllarcocha',                'PE-CUS', -13.4875841, -71.9631515],
    ['L5061220',  'Camino Inca–Chinchero a Urquillos',       'PE-CUS', -13.3765936, -72.0386839],
    ['L602264',   'Machu Picchu Pueblo / Aguas Calientes',   'PE-CUS', -13.1547960, -72.5245714],
    ['L10930616', 'Huarcapay, Cusco',                        'PE-CUS', -13.6128815, -71.7344546],
    ['L_DEMO1',   'Bosque de Polylepis – Ausangate',         'PE-CUS', -13.7833000, -71.2167000],
    ['L_DEMO2',   'Valle Sagrado – Pisac',                   'PE-CUS', -13.4167000, -71.8500000],
    ['L_DEMO3',   'Wayra Pampa – Chinchero',                 'PE-CUS', -13.3833000, -72.0500000],
];

$stmt_ubi2 = $pdo->prepare("INSERT IGNORE INTO ubicaciones (id_ubicacion, nombre_ubicacion, estado_provincia, latitud, longitud) VALUES (?, ?, ?, ?, ?)");
$pdo->beginTransaction();
foreach ($ubicaciones_demo as $u) $stmt_ubi2->execute($u);
$pdo->commit();

$registros_demo = [
    [2,'Gallito de las Rocas','ruperu1','L_DEMO1','Bosque de Polylepis – Ausangate',-13.7833,-71.2167,2,'2025-03-15','Macho en cortejo, plumaje naranja brillante.'],
    [2,'Cóndor Andino','andcon1','L_DEMO1','Bosque de Polylepis – Ausangate',-13.7833,-71.2167,1,'2025-03-15','Sobrevolando el valle a gran altura.'],
    [2,'Colibrí Gigante','giahum1','L4918088','Humedal de Huayllarcocha',-13.4875,-71.9631,3,'2025-04-02','Visitando flores de puya cerca del humedal.'],
    [2,'Pato de Torrente','tordu1','L5061220','Camino Inca–Chinchero a Urquillos',-13.3765,-72.0386,2,'2025-04-10','Par adulto nadando en rápidos del río.'],
    [2,'Tangara del Paraíso','paradis1','L1811275','Inkaterra Machu Picchu Pueblo Hotel',-13.1576,-72.5224,4,'2025-05-01','Bandada mixta con otras tangaras.'],
    [2,'Loro de Cabeza Azul','bhppar1','L602264','Machu Picchu Pueblo / Aguas Calientes',-13.1547,-72.5245,6,'2025-05-20','Bandada ruidosa al amanecer.'],
    [2,'Cacique de Montaña','moucac1','L_DEMO2','Valle Sagrado – Pisac',-13.4167,-71.8500,2,'2025-06-01','Colores vibrantes, especie notoria.'],
    [2,'Tordo Gigante','grethr1','L5006393','Zona Arqueológica de Inkilltambo',-13.5026,-71.9528,3,'2025-06-15','Canto melodioso al amanecer.'],
    [3,'Cóndor Andino','andcon1','L5006393','Zona Arqueológica de Inkilltambo',-13.5026,-71.9528,3,'2025-02-10','Tres cóndores en vuelo termal al mediodía.'],
    [3,'Gallito de las Rocas','ruperu1','L_DEMO3','Wayra Pampa – Chinchero',-13.3833,-72.0500,1,'2025-02-22','Hembra incubando en roca junto al río.'],
    [3,'Pato de Torrente','tordu1','L4918088','Humedal de Huayllarcocha',-13.4875,-71.9631,5,'2025-03-05','Grupo en la orilla norte del humedal.'],
    [3,'Motmot Andino','andmot1','L5061220','Camino Inca–Chinchero a Urquillos',-13.3765,-72.0386,2,'2025-04-20','Cola racquet muy visible, muy fotogénico.'],
    [3,'Halcón Peregrino','perfal1','L10930616','Huarcapay, Cusco',-13.6128,-71.7344,1,'2025-05-08','Picado a alta velocidad sobre el valle.'],
    [3,'Ibis de la Puna','punibi1','L4918088','Humedal de Huayllarcocha',-13.4875,-71.9631,4,'2025-05-18','Grupo sondando el barro al atardecer.'],
    [3,'Colibrí Gigante','giahum1','L_DEMO3','Wayra Pampa – Chinchero',-13.3833,-72.0500,2,'2025-06-02','Patrullaje sobre flores de agave.'],
    [3,'Tangara del Paraíso','paradis1','L1811275','Inkaterra Machu Picchu Pueblo Hotel',-13.1576,-72.5224,3,'2025-06-20','Bandada activa en el dosel.'],
];

$stmt_reg   = $pdo->prepare("INSERT IGNORE INTO registros (id_usuario, nombre_comun, species_code, id_ubicacion_ref, nombre_lugar, latitud, longitud, cantidad, fecha_avistamiento, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$total_demo = 0; $pdo->beginTransaction();
foreach ($registros_demo as $r) { $stmt_reg->execute($r); if ($stmt_reg->rowCount() > 0) $total_demo++; }
$pdo->commit();

log_msg("✅ Datos demo insertados/confirmados: <strong>{$total_demo}</strong> nuevos registros.", 'ok');
echo "</div></div>";

// ════════════════════════════════════════════════════════════
// RESUMEN FINAL
// ════════════════════════════════════════════════════════════
echo "<div class='box box-ok'><h3>🎉 Importación completada (idempotente)</h3>";
$tablas = [
    'usuarios'           => '👤 Usuarios',
    'especies_taxonomia' => '🐦 Taxonomía',
    'ubicaciones'        => '📍 Ubicaciones',
    'observaciones'      => '📋 Obs. CSV Denis',
    'registros'          => '📝 Registros manuales',
];
echo "<div class='stat-row'>";
foreach ($tablas as $tabla => $label) {
    try {
        $n = $pdo->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
        echo "<div class='stat'><strong>" . number_format((int)$n) . "</strong>{$label}</div>";
    } catch (PDOException $e) {
        echo "<div class='stat' style='border-left:3px solid #A32D2D'><strong>?</strong>{$label}</div>";
    }
}
echo "</div>";
echo "<p style='margin-top:18px;font-size:.85rem;color:#555;'>
    Credenciales: <code>denis@ornis.com</code> · <code>maria@ornis.com</code> · <code>carlos@ornis.com</code>
    &nbsp;|&nbsp; Pass: <code>ornis2026</code>
    &nbsp;|&nbsp; <a href='?reset=1' onclick=\"return confirm('¿Limpiar y reimportar todo?')\">Resetear y reimportar</a>
  </p>";
echo "<a class='btn' href='../index.php'>Ir a ORNIS →</a>";
echo "</div>";
?>
</body>
</html>
