<?php
// ============================================================
//  config/rutas.php — URL base ORNIS v3.1
//  CORRECCIÓN TOTAL: usa PHP_SELF en vez de realpath/str_replace
//  Funciona en Mac XAMPP con symlinks, Windows y Linux.
// ============================================================
declare(strict_types=1);

if (defined('BASE_URL')) return; // Evitar redefinición si se incluye dos veces

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

// ── Calcular la URL raíz del proyecto desde PHP_SELF
// PHP_SELF es siempre correcto sin importar symlinks del sistema
// Ej: PHP_SELF = "/Ornis/auth.php"         → raíz = /Ornis
//     PHP_SELF = "/Ornis/database/imp.php" → raíz = /Ornis

$self = $_SERVER['PHP_SELF'] ?? '/index.php';

// __DIR__ en este archivo = .../Ornis/config
// La raíz del proyecto está 1 nivel arriba (__DIR__/..)
// Calculamos cuántos niveles está el SCRIPT actual respecto a la raíz del proyecto
$scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$projectRootFs  = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: __DIR__ . '/..');

// Ruta relativa del script respecto a la raíz del proyecto
// Ej: scriptFilename = .../Ornis/auth.php     → rel = auth.php        → 0 niveles extra
//     scriptFilename = .../Ornis/database/i.php → rel = database/i.php → 1 nivel extra
if (str_starts_with($scriptFilename, $projectRootFs)) {
    $relScript = ltrim(substr($scriptFilename, strlen($projectRootFs)), '/');
} else {
    $relScript = ltrim(basename($scriptFilename), '/');
}
$levelsDeep = substr_count($relScript, '/'); // 0 para raíz, 1 para subdir, etc.

// Subir $levelsDeep + 1 desde el directorio del PHP_SELF para llegar a la raíz
$urlDir = dirname($self); // /Ornis  si self=/Ornis/auth.php
for ($i = 0; $i < $levelsDeep; $i++) {
    $urlDir = dirname($urlDir);
}
$relPath = rtrim($urlDir, '/');
if ($relPath === '.') $relPath = '';

define('BASE_URL',        $protocol . '://' . $host . $relPath);
define('ASSETS_URL',      BASE_URL  . '/public');
define('CSS_URL',         ASSETS_URL . '/css/style.css');
define('JS_URL',          ASSETS_URL . '/js/script.js');
define('IMG_URL',         ASSETS_URL . '/img');
define('UPLOADS_URL_ABS', ASSETS_URL . '/uploads/');
