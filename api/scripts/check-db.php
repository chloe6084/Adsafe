<?php
/**
 * DB 연결 진단 — .env 존재·키 목록, MySQL 연결 시도
 * 실행: php api/scripts/check-db.php
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
  echo "CLI에서만 실행하세요.\n";
  exit(1);
}

$apiRoot = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';
$envPath = $apiRoot . DIRECTORY_SEPARATOR . '.env';

echo "=== 1. .env 파일 ===\n";
echo "  경로: {$envPath}\n";
echo "  존재: " . (file_exists($envPath) ? '예' : '아니오') . "\n";

require_once __DIR__ . '/../lib/env_pdo.php';
$env = load_env_file($envPath);

if (!empty($env)) {
  echo "  줄 수: " . count($env) . "\n";
  foreach ($env as $k => $v) {
    $show = ($k === 'DB_PASSWORD') ? (strlen($v) ? '***' . substr($v, -4) . ' (길이 ' . strlen($v) . ')' : '(비어있음)') : $v;
    echo "  {$k}={$show}\n";
  }
}

echo "\n=== 2. env 배열 (DB_* 키) ===\n";
echo "  DB_PASSWORD: " . (isset($env['DB_PASSWORD']) && $env['DB_PASSWORD'] !== '' ? '***설정됨 (길이 ' . strlen($env['DB_PASSWORD']) . ')' : '(없음)') . "\n";
echo "  DB_HOST: " . ($env['DB_HOST'] ?? '(없음)') . "\n";
echo "  DB_PORT: " . ($env['DB_PORT'] ?? '(없음)') . "\n";
echo "  DB_USER: " . ($env['DB_USER'] ?? '(없음)') . "\n";
echo "  DB_NAME: " . ($env['DB_NAME'] ?? '(없음)') . "\n";
echo "  DB_SSL_CA: " . ($env['DB_SSL_CA'] ?? '(없음)') . "\n";

echo "\n=== 3. SSL 인증서 ===\n";
$sslPath = $env['DB_SSL_CA'] ?? null;
if ($sslPath) {
  $resolved = (preg_match('/^[A-Za-z]:\\\\|^\//', $sslPath)) ? $sslPath : (realpath($apiRoot . DIRECTORY_SEPARATOR . $sslPath) ?: $apiRoot . DIRECTORY_SEPARATOR . $sslPath);
  echo "  경로: {$resolved}\n";
  echo "  존재: " . (file_exists($resolved) ? '예' : '아니오') . "\n";
} else {
  echo "  (설정 없음)\n";
}

echo "\n=== 4. MySQL 연결 시도 ===\n";
if (empty($env['DB_PASSWORD'])) {
  echo "  비밀번호가 비어 있어 연결하지 않음. (위 1·2번에서 비밀번호 확인)\n";
} else {
  try {
    $pdo = get_pdo_cli();
    echo "  연결 성공.\n";
  } catch (Throwable $e) {
    echo "  연결 실패: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getCode') && $e->getCode()) {
      echo "  코드: " . $e->getCode() . "\n";
    }
  }
}

echo "\n=== 끝 ===\n";
