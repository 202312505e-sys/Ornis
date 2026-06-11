<?php
// ============================================================
//  config/constantes.php — Constantes globales ORNIS v3.1
//  CORRECCIÓN: ini_set de sesión movido a función explícita
//  que Auth::iniciarSesion() llama ANTES de session_start()
// ============================================================
declare(strict_types=1);

if (defined('ROOT_PATH')) return;

// ── Rutas absolutas del proyecto
define('ROOT_PATH',       realpath(__DIR__ . '/..'));
define('UPLOADS_PATH',    ROOT_PATH . '/public/uploads/');
define('DATA_PATH',       ROOT_PATH . '/database/data/');

// ── Límites de subida de fotos
define('FOTOS_EXT_OK',    ['jpg', 'jpeg', 'png', 'webp']);
define('FOTOS_MAX_BYTES', 10 * 1024 * 1024);  // 10 MB

// ── Paginación
define('REGISTROS_POR_PAGINA', 10);

// ── Duración de sesión
define('SESSION_LIFETIME', 7200); // 2 horas

/**
 * Configura las directivas de sesión segura.
 * Debe llamarse ANTES de session_start().
 */
function ornis_session_config(): void
{
    if (session_status() !== PHP_SESSION_NONE) return;
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime',  (string) SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', '0');
    // ini_set('session.cookie_secure', '1'); // activar en HTTPS
}
