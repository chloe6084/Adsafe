<?php
declare(strict_types=1);
/**
 * DB 연결 진단 (PHP)
 * 실행: php scripts/check-db.php (api 폴더에서)
 *   또는 c:\xampp2\php\php.exe scripts/check-db.php
 */

$apiRoot = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';
$envPath = $apiRoot . DIRECTORY_SEPARATOR . '.env';

echo "=== 1. .env 파일 ===\n";
echo "  경로: {$envPath}\n";
echo "  존재: " . (file_exists($envPath) ? 'true' : 'false') . "\n";

$env = [];
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    echo "  줄 수: " . count($lines) . "\n";
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $eq = strpos($line, '=');
        if ($eq === false) continue;
        $key = trim(substr($line, 0, $eq));
        $val = trim(substr($line, $eq + 1));
        $hash = strpos($val, '#');
        if ($hash !== false) $val = trim(substr($val, 0, $hash));
        $val = preg_replace('/^["\']|["\']$/', '', $val) ?? $val;
        $env[$key] = $val;
        $show = ($key === 'DB_PASSWORD')
            ? ($val ? '***' . substr($val, -4) . ' (길이 ' . strlen($val) . ')' : '(비어있음)')
            : $val;
        echo "  {$key}= {$show}\n";
    }
}

echo "\n=== 2. SSL 인증서 ===\n";
$sslCa = $env['DB_SSL_CA'] ?? '';
if ($sslCa !== '') {
    $resolved = preg_match('/^[A-Za-z]:\\\\|^\//', $sslCa) ? $sslCa : realpath($apiRoot . '/' . $sslCa);
    echo "  경로: {$resolved}\n";
    echo "  존재: " . (($resolved && file_exists($resolved)) ? 'true' : 'false') . "\n";
} else {
    echo "  (설정 없음)\n";
}

echo "\n=== 3. MySQL 연결 시도 ===\n";
$host = $env['DB_HOST'] ?? 'localhost';
$port = (int)($env['DB_PORT'] ?? '3306');
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';
$dbName = $env['DB_NAME'] ?? 'adsafe_2';

if ($pass === '') {
    echo "  비밀번호가 비어 있어 연결하지 않음.\n";
    exit(1);
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if ($sslCa !== '') {
        $caPath = preg_match('/^[A-Za-z]:\\\\|^\//', $sslCa) ? $sslCa : realpath($apiRoot . '/' . $sslCa);
        if ($caPath && file_exists($caPath)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
        }
    }
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "  연결 성공.\n";

    $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ' . $pdo->quote($dbName));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  테이블 수: " . ($row['cnt'] ?? '?') . "\n";
} catch (Throwable $e) {
    echo "  연결 실패: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== 끝 ===\n";
