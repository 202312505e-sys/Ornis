<?php
declare(strict_types=1);
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/src/Auth.php';
Auth::logout();
header('Location: auth.php?success=' . urlencode('Sesión cerrada correctamente.'));
exit;
