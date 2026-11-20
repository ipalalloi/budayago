<?php
declare(strict_types=1);
/**
 * SCK-005 P0 fail-closed integration guard.
 * Only for an explicitly provisioned, empty, local, disposable sck_ci database.
 * Independent review and live MySQL 8 / MariaDB >=10.5 verification still required.
 */
function sck_ci_refuse(string $reason): never {
    fwrite(STDERR, "SCK_CI_REFUSED: {$reason}\n");
    exit(2);
}

function sck_ci_validate_dsn(string $dsn): bool {
    // SCK-005 G2: match the dedicated BUDAYAGO instance only.
    return preg_match('/\Amysql:host=127\.0\.0\.1;port=3316;dbname=sck_ci;charset=utf8mb4\z/D', $dsn) === 1;
}

function sck_ci_preflight(): void {
    if (PHP_SAPI !== 'cli') {
        sck_ci_refuse('CLI execution only');
    }
    if (getenv('SCK_ALLOW_TEST_DB') !== 'yes') {
        sck_ci_refuse('explicit test opt-in missing');
    }
    if (is_file(dirname(__DIR__) . '/config.php')) {
        // bootstrap.php prioritizes config.php over environment variables.
        sck_ci_refuse('config.php exists and could override DB_DSN');
    }
    if (!sck_ci_validate_dsn((string) (getenv('DB_DSN') ?: ''))) {
        sck_ci_refuse('DB_DSN must select only local sck_ci over TCP');
    }
    $user = (string) (getenv('DB_USER') ?: '');
    if ($user === '' || strcasecmp($user, 'root') === 0) {
        sck_ci_refuse('dedicated non-root database user required');
    }
    if ((string) (getenv('DB_PASS') ?: '') === '') {
        sck_ci_refuse('dedicated database password required');
    }
    $token = (string) (getenv('SCK_CI_SENTINEL_TOKEN') ?: '');
    if (!preg_match('/\A[a-f0-9]{64}\z/D', $token)) {
        sck_ci_refuse('64-character lowercase hex sentinel token required');
    }
}

function sck_ci_supported_server(string $version): bool {
    $maria = stripos($version, 'mariadb') !== false;
    preg_match_all('/\d+\.\d+\.\d+/', $version, $matches);
    if (!$matches[0]) {
        return false;
    }
    // MariaDB may report a compatibility prefix 5.5.5- before the real version.
    $numeric = $maria ? end($matches[0]) : $matches[0][0];
    return version_compare($numeric, $maria ? '10.5.0' : '8.0.0', '>=');
}

function sck_ci_assert_database(PDO $pdo): void {
    $name = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($name !== 'sck_ci') {
        sck_ci_refuse('connected schema is not sck_ci');
    }
    $activeAccount = (string) $pdo->query('SELECT CURRENT_USER()')->fetchColumn();
    $expectedUser = (string) getenv('DB_USER');
    $activeUser = explode('@', $activeAccount, 2)[0];
    if ($activeUser !== $expectedUser || strcasecmp($activeUser, 'root') === 0) {
        sck_ci_refuse('authenticated database user is not the dedicated test user');
    }
    $serverVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    if (!sck_ci_supported_server($serverVersion)) {
        sck_ci_refuse('server version is below MySQL 8.0 or MariaDB 10.5');
    }

    $required = ['users', 'knowledge', 'lessons', 'attempts', 'audit_events', '_sck_ci_guard'];
    $allowed = array_merge($required, ['login_throttles']); // Optional SCK-004 candidate table.
    $observed = $pdo->query(
        "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($required as $table) {
        if (!in_array($table, $observed, true)) {
            sck_ci_refuse('required test table missing: ' . $table);
        }
    }
    foreach ($observed as $table) {
        if (!in_array($table, $allowed, true)) {
            sck_ci_refuse('unexpected table in disposable schema');
        }
    }

    $sentinelHash = $pdo->query('SELECT token_sha256 FROM _sck_ci_guard WHERE guard_id = 1')->fetchColumn();
    $token = (string) getenv('SCK_CI_SENTINEL_TOKEN');
    if (!is_string($sentinelHash) || !hash_equals(strtolower($sentinelHash), hash('sha256', $token))) {
        sck_ci_refuse('disposable sentinel token mismatch');
    }

    // No deletion/cleanup is performed. Refuse any non-empty functional table.
    foreach (['audit_events', 'attempts', 'lessons', 'knowledge', 'users'] as $table) {
        if ((int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() !== 0) {
            sck_ci_refuse('test schema is not empty; no cleanup performed');
        }
    }
    if (in_array('login_throttles', $observed, true) &&
        (int) $pdo->query('SELECT COUNT(*) FROM `login_throttles`')->fetchColumn() !== 0) {
        sck_ci_refuse('test throttle table is not empty');
    }
}
