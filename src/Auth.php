<?php
// ============================================================
//  src/Auth.php — Autenticación ORNIS v3.1
//  CORRECCIONES:
//  ✅ iniciarSesion() llama ornis_session_config() antes de session_start()
//  ✅ login() es método de instancia que recibe $pdo correctamente
//  ✅ activo=1 en query (columna existe en esquema corregido)
//  ✅ bcrypt costo 12 consistente
//  ✅ Anti session-fixation y anti-hijacking
// ============================================================
declare(strict_types=1);

class Auth
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── Iniciar sesión segura (llama config antes de session_start) ──
    public static function iniciarSesion(): void
    {
        if (session_status() !== PHP_SESSION_NONE) return;
        if (function_exists('ornis_session_config')) {
            ornis_session_config(); // ini_set ANTES de session_start
        }
        session_start();
    }

    // ── LOGIN ────────────────────────────────────────────────────────
    public function login(string $email, string $password): array
    {
        if (empty($email) || empty($password)) {
            return ['ok' => false, 'msg' => 'Completa todos los campos.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Correo inválido.'];
        }

        $stmt = $this->pdo->prepare("
            SELECT id_usuario, nombre, apellido, email, password, rol, avatar
            FROM   usuarios
            WHERE  email  = :email
              AND  activo = 1
            LIMIT  1
        ");
        $stmt->execute([':email' => mb_strtolower(trim($email))]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($password, $u['password'])) {
            return ['ok' => false, 'msg' => 'Credenciales incorrectas.'];
        }

        // Regenerar ID anti session-fixation
        session_regenerate_id(true);

        $_SESSION['usuario_id']       = (int) $u['id_usuario'];
        $_SESSION['usuario_nombre']   = $u['nombre'];
        $_SESSION['usuario_apellido'] = $u['apellido'];
        $_SESSION['usuario_email']    = $u['email'];
        $_SESSION['usuario_rol']      = $u['rol'];
        $_SESSION['usuario_avatar']   = $u['avatar'];
        $_SESSION['_ip']              = $_SERVER['REMOTE_ADDR']     ?? '';
        $_SESSION['_ua']              = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['_time']            = time();

        // Auto-rehash si el costo es inferior a 12
        if (password_needs_rehash($u['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $nuevo = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->pdo->prepare("UPDATE usuarios SET password=? WHERE id_usuario=?")
                      ->execute([$nuevo, $u['id_usuario']]);
        }

        return ['ok' => true];
    }

    // ── REGISTRO ─────────────────────────────────────────────────────
    public function registrar(
        string $nombre, string $apellido,
        string $email,  string $password
    ): array {
        $nombre   = trim($nombre);
        $apellido = trim($apellido);
        $email    = mb_strtolower(trim($email));
        $password = trim($password);

        if (!$nombre || !$apellido || !$email || !$password) {
            return ['ok' => false, 'msg' => 'Todos los campos son obligatorios.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Correo inválido.'];
        }
        if (mb_strlen($password) < 6) {
            return ['ok' => false, 'msg' => 'Mínimo 6 caracteres.'];
        }

        $check = $this->pdo->prepare("SELECT id_usuario FROM usuarios WHERE email=? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) {
            return ['ok' => false, 'msg' => 'Este correo ya está registrado.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (nombre, apellido, email, password, rol, activo)
            VALUES (?, ?, ?, ?, 'observador', 1)
        ");
        $stmt->execute([$nombre, $apellido, $email, $hash]);

        return ['ok' => true, 'id' => (int) $this->pdo->lastInsertId()];
    }

    // ── LOGOUT ───────────────────────────────────────────────────────
    public static function logout(): void
    {
        self::iniciarSesion();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── VERIFICAR sesión activa ───────────────────────────────────────
    public static function verificar(): bool
    {
        self::iniciarSesion();
        if (empty($_SESSION['usuario_id'])) return false;

        // Anti-hijacking: IP + User-Agent
        $ipOk = ($_SESSION['_ip'] === ($_SERVER['REMOTE_ADDR']     ?? ''));
        $uaOk = ($_SESSION['_ua'] === ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (!$ipOk || !$uaOk) { self::logout(); return false; }

        // Expirar por inactividad
        if (defined('SESSION_LIFETIME') && isset($_SESSION['_time'])) {
            if ((time() - $_SESSION['_time']) > SESSION_LIFETIME) {
                self::logout(); return false;
            }
            $_SESSION['_time'] = time();
        }
        return true;
    }

    // ── GUARD para páginas privadas ───────────────────────────────────
    public static function requerir(string $redirect = 'auth.php'): void
    {
        if (!self::verificar()) {
            header("Location: {$redirect}");
            exit;
        }
    }

    // ── Getters escapados ─────────────────────────────────────────────
    public static function id(): int    { return (int)($_SESSION['usuario_id'] ?? 0); }
    public static function nombre(): string {
        return htmlspecialchars($_SESSION['usuario_nombre'] ?? '', ENT_QUOTES, 'UTF-8');
    }
    public static function rol(): string  { return $_SESSION['usuario_rol'] ?? 'observador'; }
    public static function esAdmin(): bool { return self::rol() === 'admin'; }
}
