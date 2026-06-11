<?php
// ============================================================
//  config/conexion.php — Conexión PDO centralizada ORNIS v3
//  Incluir con: require_once __DIR__ . '/../config/conexion.php';
// ============================================================

declare(strict_types=1);

// ── Parámetros de conexión (ajustar en producción con variables de entorno)
define('DB_HOST',    'localhost');
define('DB_NAME',    'ornis_db');
define('DB_USER',    'root');
define('DB_PASS',    '');           // Vacío por defecto en XAMPP / Mac
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT',    '3306');

// ── ID del usuario principal Denis (para importación de CSV)
define('DENIS_USER_ID', 1);

// ── DSN para MySQL con puerto explícito
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
);

// ── Opciones PDO recomendadas OWASP
$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lanza PDOException
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                     // Prepared statements reales
    PDO::ATTR_PERSISTENT         => false,                     // Sin conexiones persistentes
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    PDO::MYSQL_ATTR_FOUND_ROWS   => true,                     // rowCount() correcto en UPDATE
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
} catch (PDOException $e) {
    // Registrar en log sin exponer detalles al usuario
    error_log('[ORNIS-BD] ' . $e->getMessage());

    // Respuesta genérica según contexto (JSON o HTML)
    $esAjax = (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    );

    if ($esAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(503);
        die(json_encode(['error' => 'Servicio no disponible temporalmente.']));
    }

    http_response_code(503);
    die('<p style="font-family:sans-serif;color:#721c24;padding:20px;">
          Error: No se pudo conectar a la base de datos.
          Verifique que MySQL esté activo en XAMPP.
         </p>');
}
