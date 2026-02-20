<?php
declare(strict_types=1);

/**
 * CLI 전용: .env 로드 및 PDO 생성 (헤더 출력 없음).
 * seed.php, check-db.php 등에서 사용.
 */

function load_env_file(string $path): array {
  if (!file_exists($path)) return [];
  $lines = file($path, FILE_IGNORE_NEW_LINES);
  if ($lines === false) return [];
  $out = [];
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) continue;
    $eq = strpos($line, '=');
    if ($eq === false) continue;
    $k = trim(substr($line, 0, $eq));
    $v = trim(substr($line, $eq + 1));
    $v = str_replace("\r", '', $v);
    $hash = strpos($v, '#');
    if ($hash !== false) $v = trim(substr($v, 0, $hash));
    $v = preg_replace('/^["\']|["\']$/', '', $v);
    if ($k !== '') $out[$k] = $v;
  }
  return $out;
}

function pdo_from_env(array $env): PDO {
  $host = $env['DB_HOST'] ?? 'localhost';
  $port = (int)($env['DB_PORT'] ?? '3306');
  $user = $env['DB_USER'] ?? 'root';
  $pass = $env['DB_PASSWORD'] ?? '';
  $db   = $env['DB_NAME'] ?? 'adsafe_2';

  $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];

  if (!empty($env['DB_SSL_CA'])) {
    $ca = $env['DB_SSL_CA'];
    if (!preg_match('/^[A-Za-z]:\\\\|^\//', $ca)) {
      $ca = realpath(__DIR__ . '/..' . DIRECTORY_SEPARATOR . $ca) ?: $ca;
    }
    if (file_exists($ca)) {
      $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
    }
  }

  return new PDO($dsn, $user, $pass, $options);
}

function get_pdo_cli(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;
  $envPath = __DIR__ . '/../.env';
  $env = load_env_file($envPath);
  $pdo = pdo_from_env($env);
  return $pdo;
}
