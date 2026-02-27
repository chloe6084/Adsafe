<?php
declare(strict_types=1);

/**
 * 법령 개정 모니터링 API
 * - GET  /api/law-monitor/status
 * - POST /api/law-monitor/config
 * - POST /api/law-monitor/check
 */

function law_monitor_default_config(): array {
  return [
    'monitorUrl' => 'https://www.law.go.kr/LSW//lsInfoP.do?lsiSeq=283151&chrClsCd=010202&urlMode=lsInfoP&efYd=20260210&ancYnChk=0#0000',
    'slackWebhookUrl' => '',
    'enabled' => true,
    'patternVersion' => '\[(?<version>대통령령(?:\s|&nbsp;|&#160;)*제\d+호,(?:\s|&nbsp;|&#160;)*\d{4}\.(?:\s|&nbsp;|&#160;)*\d{1,2}\.(?:\s|&nbsp;|&#160;)*\d{1,2}\.,(?:\s|&nbsp;|&#160;)*[^\]]+)\]',
    'patternRevDate' => ',\s*(?<rev_date>\d{4}\.\s*\d{1,2}\.\s*\d{1,2})\.',
  ];
}

function law_monitor_state_path(): string {
  $dir = __DIR__ . '/../storage';
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }
  return $dir . '/law_monitor_state.json';
}

function law_monitor_load_state(): array {
  $default = [
    'config' => law_monitor_default_config(),
    'lastCheckedAt' => null,
    'lastChangedAt' => null,
    'lastVersion' => null,
    'lastRevDate' => null,
    'lastError' => null,
    'lastSlackStatus' => null,
  ];

  $path = law_monitor_state_path();
  if (!file_exists($path)) return $default;

  $raw = @file_get_contents($path);
  if ($raw === false || trim($raw) === '') return $default;
  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) return $default;

  $state = array_merge($default, $decoded);
  if (!isset($state['config']) || !is_array($state['config'])) {
    $state['config'] = law_monitor_default_config();
  } else {
    $state['config'] = array_merge(law_monitor_default_config(), $state['config']);
  }
  return $state;
}

function law_monitor_save_state(array $state): void {
  $path = law_monitor_state_path();
  $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json === false) {
    throw new RuntimeException('상태 JSON 직렬화에 실패했습니다.');
  }
  if (@file_put_contents($path, $json, LOCK_EX) === false) {
    throw new RuntimeException('상태 파일 저장에 실패했습니다.');
  }
}

function law_monitor_public_state(array $state): array {
  $out = $state;
  $cfg = $out['config'] ?? [];
  if (is_array($cfg)) {
    $cfg['slackWebhookConfigured'] = !empty($cfg['slackWebhookUrl']);
    unset($cfg['slackWebhookUrl']);
    $out['config'] = $cfg;
  }
  return $out;
}

function law_monitor_normalize_spaces(string $text): string {
  $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;
  $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
  return trim($text);
}

function law_monitor_normalize_rev_date(string $date): string {
  $clean = preg_replace('/\s+/u', '', $date) ?? $date;
  return trim($clean);
}

function law_monitor_fetch_html(string $url): string {
  $ctx = stream_context_create([
    'http' => [
      'method' => 'GET',
      'timeout' => 15,
      'ignore_errors' => true,
      'header' => "User-Agent: AdSafe-LawMonitor/1.0\r\nAccept: text/html,application/xhtml+xml\r\n",
    ],
  ]);
  $body = @file_get_contents($url, false, $ctx);
  if ($body === false || trim($body) === '') {
    throw new RuntimeException('법령 페이지 요청에 실패했습니다.');
  }
  return $body;
}

function law_monitor_post_slack(string $webhookUrl, array $payload): array {
  $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
  if ($json === false) {
    throw new RuntimeException('Slack 페이로드 직렬화 실패');
  }
  $ctx = stream_context_create([
    'http' => [
      'method' => 'POST',
      'timeout' => 10,
      'ignore_errors' => true,
      'header' => "Content-Type: application/json\r\n",
      'content' => $json,
    ],
  ]);
  $resp = @file_get_contents($webhookUrl, false, $ctx);
  $headers = $http_response_header ?? [];
  $statusLine = $headers[0] ?? '';
  $ok = is_string($statusLine) && str_contains($statusLine, ' 200 ');
  return [
    'ok' => $ok,
    'statusLine' => $statusLine,
    'response' => is_string($resp) ? $resp : '',
  ];
}

function handle_get_law_monitor_status(): void {
  try {
    $state = law_monitor_load_state();
    json_response(law_monitor_public_state($state));
  } catch (Throwable $e) {
    json_response(['error' => '모니터링 상태 조회 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

function handle_update_law_monitor_config(): void {
  try {
    $body = read_json_body();
    $state = law_monitor_load_state();
    $config = $state['config'];

    if (isset($body['monitorUrl'])) {
      $url = trim((string)$body['monitorUrl']);
      if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        json_response(['error' => '유효한 모니터링 URL을 입력하세요.'], 400);
        return;
      }
      $config['monitorUrl'] = $url;
    }
    if (isset($body['slackWebhookUrl'])) {
      $hook = trim((string)$body['slackWebhookUrl']);
      if ($hook !== '' && !filter_var($hook, FILTER_VALIDATE_URL)) {
        json_response(['error' => '유효한 Slack Webhook URL을 입력하세요.'], 400);
        return;
      }
      $config['slackWebhookUrl'] = $hook;
    }
    if (isset($body['enabled'])) {
      $config['enabled'] = (bool)$body['enabled'];
    }

    $state['config'] = $config;
    law_monitor_save_state($state);
    json_response([
      'message' => '법령 모니터링 설정이 저장되었습니다.',
      'state' => law_monitor_public_state($state),
    ]);
  } catch (Throwable $e) {
    json_response(['error' => '모니터링 설정 저장 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

function handle_check_law_monitor(): void {
  try {
    $state = law_monitor_load_state();
    $cfg = $state['config'];

    if (empty($cfg['enabled'])) {
      json_response(['error' => '모니터링이 비활성화되어 있습니다.'], 400);
      return;
    }
    $url = (string)($cfg['monitorUrl'] ?? '');
    if ($url === '') {
      json_response(['error' => '모니터링 URL이 비어 있습니다.'], 400);
      return;
    }

    $html = law_monitor_fetch_html($url);
    $versionPattern = '/'.$cfg['patternVersion'].'/u';
    if (!preg_match($versionPattern, $html, $mv) || empty($mv['version'])) {
      $state['lastCheckedAt'] = date('c');
      $state['lastError'] = '버전 문자열 파싱 실패';
      law_monitor_save_state($state);
      json_response(['error' => '버전 문자열 파싱 실패'], 422);
      return;
    }

    $rawVersion = law_monitor_normalize_spaces((string)$mv['version']);
    $datePattern = '/'.$cfg['patternRevDate'].'/u';
    if (!preg_match($datePattern, $rawVersion, $md) || empty($md['rev_date'])) {
      $state['lastCheckedAt'] = date('c');
      $state['lastError'] = '개정일 파싱 실패';
      law_monitor_save_state($state);
      json_response(['error' => '개정일 파싱 실패'], 422);
      return;
    }
    $revDate = law_monitor_normalize_rev_date((string)$md['rev_date']);

    $prevVersion = $state['lastVersion'] ?? null;
    $prevRevDate = $state['lastRevDate'] ?? null;
    $hasBaseline = !empty($prevVersion) || !empty($prevRevDate);
    $changed = $hasBaseline && ($prevVersion !== $rawVersion || $prevRevDate !== $revDate);

    $state['lastCheckedAt'] = date('c');
    $state['lastError'] = null;
    $state['lastVersion'] = $rawVersion;
    $state['lastRevDate'] = $revDate;

    $slackResult = null;
    if ($changed && !empty($cfg['slackWebhookUrl'])) {
      $text = "*[AdSafe 법령 모니터링] 개정 감지*\n"
        . "- 대상: {$url}\n"
        . "- 이전 개정일: " . ($prevRevDate ?: '-') . "\n"
        . "- 현재 개정일: {$revDate}\n"
        . "- 현재 버전: {$rawVersion}";
      $slackResult = law_monitor_post_slack((string)$cfg['slackWebhookUrl'], ['text' => $text]);
      $state['lastSlackStatus'] = $slackResult;
    }

    if ($changed) {
      $state['lastChangedAt'] = date('c');
    }

    law_monitor_save_state($state);
    json_response([
      'message' => $changed ? '변경이 감지되었습니다.' : '변경 사항이 없습니다.',
      'changed' => $changed,
      'baselineCreated' => !$hasBaseline,
      'current' => ['version' => $rawVersion, 'revDate' => $revDate],
      'previous' => ['version' => $prevVersion, 'revDate' => $prevRevDate],
      'slack' => $slackResult,
      'state' => law_monitor_public_state($state),
    ]);
  } catch (Throwable $e) {
    json_response(['error' => '모니터링 체크 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

