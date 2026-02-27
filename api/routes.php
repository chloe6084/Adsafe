<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/handlers/health.php';
require_once __DIR__ . '/handlers/inspection_history.php';
require_once __DIR__ . '/handlers/inspect.php';
require_once __DIR__ . '/handlers/quiz.php';
require_once __DIR__ . '/handlers/users.php';
require_once __DIR__ . '/handlers/auth.php';
require_once __DIR__ . '/handlers/taxonomy.php';
require_once __DIR__ . '/handlers/rules.php';
require_once __DIR__ . '/handlers/credits.php';
require_once __DIR__ . '/handlers/ai_generate.php';
require_once __DIR__ . '/handlers/quiz_admin.php';
require_once __DIR__ . '/handlers/law_monitor.php';

// Normalize path: remove query string, strip API mount prefix
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
if (!is_string($path)) $path = '/';

// Determine mount prefix from SCRIPT_NAME (works for /api or /AdSafe/api)
$script = $_SERVER['SCRIPT_NAME'] ?? '/api/index.php';
$mount = rtrim(str_replace('\\', '/', dirname($script)), '/');
if ($mount === '') $mount = '/';
if ($mount !== '/' && str_starts_with($path, $mount)) {
  $path = substr($path, strlen($mount));
  if ($path === '') $path = '/';
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Routing
if ($method === 'GET' && $path === '/health') {
  handle_health();
}

if ($path === '/inspect' && $method === 'POST') {
  handle_inspect();
}

if ($method === 'GET' && $path === '/inspection-history') {
  handle_inspection_history_list();
}

if ($method === 'GET' && preg_match('#^/inspection-history/(\d+)$#', $path, $m)) {
  handle_inspection_history_detail((int)$m[1]);
}

// ===== AduSafe (퀴즈) API =====
if ($method === 'GET' && $path === '/quizzes') {
  handle_get_quizzes();
}

if ($method === 'GET' && $path === '/quizzes/wrong') {
  handle_get_wrong_quizzes();
}

if ($method === 'POST' && $path === '/quiz-attempts') {
  handle_create_quiz_attempt();
}

if ($method === 'POST' && preg_match('#^/quiz-attempts/(\d+)/submit$#', $path, $m)) {
  handle_submit_quiz_attempt((int)$m[1]);
}

if ($method === 'GET' && $path === '/learning-progress') {
  handle_get_learning_progress();
}

// ===== 사용자 API =====
if ($method === 'GET' && $path === '/users') {
  handle_get_users();
}

if ($method === 'GET' && preg_match('#^/users/(\d+)$#', $path, $m)) {
  handle_get_user((int)$m[1]);
}

if ($method === 'POST' && $path === '/users') {
  handle_create_user();
}

if ($method === 'PUT' && preg_match('#^/users/(\d+)$#', $path, $m)) {
  handle_update_user((int)$m[1]);
}

if ($method === 'DELETE' && preg_match('#^/users/(\d+)$#', $path, $m)) {
  handle_delete_user((int)$m[1]);
}

// 회원탈퇴 (본인 계정)
if ($method === 'POST' && $path === '/users/withdraw') {
  handle_withdraw_user();
}

// ===== 인증 API =====
if ($method === 'POST' && $path === '/auth/login') {
  handle_login();
}

if ($method === 'GET' && $path === '/auth/user') {
  handle_get_user_by_email();
}

// ===== 택소노미 API =====
if ($method === 'GET' && $path === '/taxonomy') {
  handle_get_taxonomy();
}

if ($method === 'POST' && $path === '/taxonomy') {
  handle_create_taxonomy();
}

if ($method === 'PUT' && preg_match('#^/taxonomy/([A-Z0-9_]+)$#', $path, $m)) {
  handle_update_taxonomy($m[1]);
}

if ($method === 'DELETE' && preg_match('#^/taxonomy/([A-Z0-9_]+)$#', $path, $m)) {
  handle_delete_taxonomy($m[1]);
}

// ===== 룰셋 버전 API =====
if ($method === 'GET' && $path === '/rule-set-versions') {
  handle_get_rule_set_versions();
}

if ($method === 'POST' && $path === '/rule-set-versions') {
  handle_create_rule_set_version();
}

if ($method === 'DELETE' && preg_match('#^/rule-set-versions/(\d+)$#', $path, $m)) {
  handle_delete_rule_set_version((int)$m[1]);
}

if ($method === 'PUT' && preg_match('#^/rule-set-versions/(\d+)/activate$#', $path, $m)) {
  handle_activate_rule_set_version((int)$m[1]);
}

if ($method === 'PUT' && preg_match('#^/rule-set-versions/(\d+)/deactivate$#', $path, $m)) {
  handle_deactivate_rule_set_version((int)$m[1]);
}

// ===== 룰 관리 API =====
if ($method === 'GET' && $path === '/rules') {
  handle_get_rules();
}

if ($method === 'POST' && $path === '/rules') {
  handle_create_rule();
}

if ($method === 'PUT' && preg_match('#^/rules/(\d+)$#', $path, $m)) {
  handle_update_rule((int)$m[1]);
}

if ($method === 'DELETE' && preg_match('#^/rules/(\d+)$#', $path, $m)) {
  handle_delete_rule((int)$m[1]);
}

// ===== 크레딧 API =====
if ($method === 'GET' && $path === '/credits/balance') {
  handle_get_credit_balance();
}

if ($method === 'GET' && $path === '/credits/plans') {
  handle_get_credit_plans();
}

if ($method === 'POST' && $path === '/credits/check') {
  handle_check_credit();
}

if ($method === 'POST' && $path === '/credits/use') {
  handle_use_credit();
}

if ($method === 'POST' && $path === '/credits/grant') {
  handle_grant_credit();
}

if ($method === 'PUT' && $path === '/credits/plan') {
  handle_change_plan();
}

if ($method === 'GET' && $path === '/credits/transactions') {
  handle_get_credit_transactions();
}

// ===== AI 광고문구 생성 API =====
if ($method === 'POST' && $path === '/ai/generate') {
  handle_ai_generate();
}

if ($method === 'GET' && $path === '/ai/history') {
  handle_ai_history_list();
}

if ($method === 'GET' && preg_match('#^/ai/history/(\d+)$#', $path, $m)) {
  handle_ai_history_detail((int)$m[1]);
}

// ===== 관리자 퀴즈 관리 API =====
if ($method === 'GET' && $path === '/admin/quizzes') {
  handle_admin_get_quizzes();
}

if ($method === 'GET' && preg_match('#^/admin/quizzes/(\d+)$#', $path, $m)) {
  handle_admin_get_quiz((int)$m[1]);
}

if ($method === 'POST' && $path === '/admin/quizzes') {
  handle_admin_create_quiz();
}

if ($method === 'PUT' && preg_match('#^/admin/quizzes/(\d+)$#', $path, $m)) {
  handle_admin_update_quiz((int)$m[1]);
}

if ($method === 'DELETE' && preg_match('#^/admin/quizzes/(\d+)$#', $path, $m)) {
  handle_admin_delete_quiz((int)$m[1]);
}

if ($method === 'PUT' && preg_match('#^/admin/quizzes/(\d+)/toggle$#', $path, $m)) {
  handle_admin_toggle_quiz((int)$m[1]);
}

// ===== 법령 모니터링 API =====
if ($method === 'GET' && $path === '/law-monitor/status') {
  handle_get_law_monitor_status();
}

if ($method === 'POST' && $path === '/law-monitor/config') {
  handle_update_law_monitor_config();
}

if ($method === 'POST' && $path === '/law-monitor/check') {
  handle_check_law_monitor();
}

json_response(['error' => 'Not Found', 'path' => $path], 404);

