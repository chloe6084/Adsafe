<?php
/**
 * 검수 API 호출 후 DB 저장(saveError) 여부 진단
 * 실행: php api/scripts/test-inspect-save.php
 * - POST /api/inspect 호출 후 응답에 saveError 유무 출력
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
  echo "CLI에서만 실행하세요.\n";
  exit(1);
}

require_once __DIR__ . '/../lib/env_pdo.php';
$env = load_env_file(__DIR__ . '/../.env');
$baseUrl = rtrim($env['BASE_URL'] ?? 'http://localhost/AdSafe', '/');
$inspectUrl = $baseUrl . '/api/inspect';

echo "=== 검수 저장 진단 (API 호출) ===\n\n";
echo "  URL: {$inspectUrl}\n";

$body = json_encode([
  'text' => '50% 할인 이벤트 진행 중입니다.',
  'user_id' => 1,
  'title' => '테스트',
]);

$ctx = stream_context_create([
  'http' => [
    'method' => 'POST',
    'header' => "Content-Type: application/json\r\n",
    'content' => $body,
    'timeout' => 15,
  ],
]);

$response = @file_get_contents($inspectUrl, false, $ctx);
if ($response === false) {
  echo "  요청 실패. Apache가 실행 중인지, {$baseUrl}/api/health 가 열리는지 확인하세요.\n";
  echo "  해결: php api/scripts/seed.php 를 실행한 뒤 다시 시도하세요.\n";
  exit(1);
}

$data = json_decode($response, true);
if (!is_array($data)) {
  echo "  응답 파싱 실패.\n";
  exit(1);
}

if (!empty($data['saveError'])) {
  echo "  saveError 있음: " . (is_string($data['saveError']) ? $data['saveError'] : json_encode($data['saveError'], JSON_UNESCAPED_UNICODE)) . "\n";
  echo "  해결: php api/scripts/seed.php 를 실행한 뒤 다시 시도하세요.\n";
  exit(1);
}

$runId = $data['runId'] ?? null;
echo "  runId: " . ($runId !== null ? (string) $runId : '(없음)') . "\n";
echo "  saveError: 없음\n";
echo "\n=> DB 저장 가능 상태입니다. 검수 실행 시 이력에 남아야 합니다.\n";
echo "   이력이 안 남으면: 브라우저에서 검수 후 F12 > Network에서 /api/inspect 응답에 saveError 가 있는지 확인하세요.\n";
