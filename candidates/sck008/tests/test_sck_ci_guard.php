<?php
declare(strict_types=1);
require __DIR__ . '/sck_ci_guard.php';
$cases = [
  ['mysql:host=127.0.0.1;port=3316;dbname=sck_ci;charset=utf8mb4', true],
  ['mysql:host=127.0.0.1;port=3306;dbname=sck_ci;charset=utf8mb4', false],
  ['mysql:host=127.0.0.1;port=3307;dbname=sck_ci;charset=utf8mb4', false],
  ['mysql:host=127.0.0.1;port=3306;dbname=sck_ci_backup;charset=utf8mb4', false],
  ['mysql:host=127.0.0.1;port=3306;dbname=sck_ci_prod;charset=utf8mb4', false],
  ['mysql:host=remote.example;port=3306;dbname=sck_ci;charset=utf8mb4', false],
  ['mysql:unix_socket=/tmp/mysql.sock;dbname=sck_ci;charset=utf8mb4', false],
  ['mysql:host=127.0.0.1;port=0;dbname=sck_ci;charset=utf8mb4', false],
  ['mysql:host=127.0.0.1;port=65536;dbname=sck_ci;charset=utf8mb4', false],
  ['mysql:host=127.0.0.1;port=3306;dbname=sck_ci;charset=utf8mb4;dbname=prod', false],
  ['mysql:host=127.0.0.1;dbname=sck_ci;port=3306;charset=utf8mb4', false],
];
foreach ($cases as $i => [$dsn, $expected]) {
  if (sck_ci_validate_dsn($dsn) !== $expected) {
    fwrite(STDERR, "FAIL: DSN negative/positive case " . ($i + 1) . "\n"); exit(1);
  }
}
$versions = [
  ['10.4.32-MariaDB', false],
  ['10.5.0-MariaDB', true],
  ['11.4.13-MariaDB', true],
  ['5.5.5-10.4.32-MariaDB', false],
  ['5.5.5-10.5.0-MariaDB', true],
  ['8.0.36', true],
  ['5.7.44', false],
];
foreach ($versions as $i => [$version, $expected]) {
  if (sck_ci_supported_server($version) !== $expected) {
    fwrite(STDERR, "FAIL: server version case " . ($i + 1) . "\n"); exit(1);
  }
}
echo 'PASS: ' . count($cases) . ' DSN cases; ' . count($versions) . " version cases (no database connection)\n";
