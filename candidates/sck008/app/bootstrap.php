<?php
declare(strict_types=1);

define('SCK_ROOT', dirname(__DIR__));
$localConfig = is_file(SCK_ROOT . '/config.php') ? require SCK_ROOT . '/config.php' : [];
if (!is_array($localConfig)) { throw new RuntimeException('Invalid config'); }
$GLOBALS['sck_config'] = $localConfig;

function cfg(string $key, string $fallback = ''): string {
    $value = $GLOBALS['sck_config'][$key] ?? getenv($key);
    return is_string($value) && $value !== '' ? $value : $fallback;
}
function database(): PDO {
    static $connection = null;
    if ($connection !== null) { return $connection; }
    $dsn = cfg('DB_DSN');
    if ($dsn === '') { throw new RuntimeException('Database is not configured'); }
    $connection = new PDO($dsn, cfg('DB_USER'), cfg('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $connection;
}
function query(string $sql, array $values = []): PDOStatement {
    $statement = database()->prepare($sql);
    $statement->execute($values);
    return $statement;
}
function escape(?string $text): string { return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function page_url(string $page, array $params = []): string { return 'index.php?' . http_build_query(['p' => $page] + $params); }
function redirect_to(string $page, array $params = []): never {
    header('Location: ' . page_url($page, $params), true, 303);
    exit;
}
function message(string $text): void { $_SESSION['sck_message'] = $text; }
function csrf_token(): string {
    if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_input(): string { return '<input type="hidden" name="csrf" value="' . escape(csrf_token()) . '">'; }
function verify_csrf(): void {
    if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(csrf_token(), $_POST['csrf'])) {
        http_response_code(403);
        exit('Permintaan ditolak: token keamanan tidak valid.');
    }
}
function current_user(): ?array {
    static $cached = null; static $loaded = false;
    if (!$loaded) {
        $loaded = true;
        $id = (int)($_SESSION['uid'] ?? 0);
        $cached = $id ? (query('SELECT id, name, email, role, active FROM users WHERE id = ?', [$id])->fetch() ?: null) : null;
        if ($cached === null || !(bool)($cached['active'] ?? false)) { $cached = null; unset($_SESSION['uid']); }
    }
    return $cached;
}
function require_login(): array {
    $u = current_user();
    if (!$u) { redirect_to('login'); }
    return $u;
}
function require_roles(array $roles): array {
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) { http_response_code(403); exit('Akses ditolak.'); }
    return $u;
}
function audit_event(?int $uid, string $action, string $entity, int $id, string $detail = ''): void {
    query('INSERT INTO audit_events (actor_id, action, entity_type, entity_id, detail) VALUES (?, ?, ?, ?, ?)', [$uid, $action, $entity, $id, substr($detail, 0, 250)]);
}
function log_in(int $id): void { session_regenerate_id(true); $_SESSION['uid'] = $id; unset($_SESSION['csrf']); }
function log_out(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'], 'domain' => $params['domain'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}
function boot_http(): void {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    if (cfg('FORCE_HTTPS', 'false') === 'true' && !$https) {
        // Do not rely on user-supplied Host to build a redirect.
        $origin = cfg('APP_ORIGIN');
        if (!str_starts_with($origin, 'https://')) { http_response_code(503); exit('HTTPS origin not configured'); }
        header('Location: ' . rtrim($origin, '/') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 308);
        exit;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_name('SCKSESSION');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
    if (!empty($_SESSION['uid'])) {
        if (isset($_SESSION['last_seen']) && (time() - (int)$_SESSION['last_seen']) > 1800) {
            unset($_SESSION['uid']); session_regenerate_id(true);
        }
        $_SESSION['last_seen'] = time();
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
    header('Cache-Control: no-store');
    if ($https) { header('Strict-Transport-Security: max-age=31536000'); }
}
