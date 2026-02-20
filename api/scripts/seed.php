<?php
/**
 * AdSafe DB 시드: workspaces 1건, users 1건, risk_taxonomy, rule_set_versions, rules, quizzes, quiz_choices
 * 실행: php api/scripts/seed.php (프로젝트 루트 또는 api 폴더에서)
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
  echo "CLI에서만 실행하세요.\n";
  exit(1);
}

require_once __DIR__ . '/../lib/env_pdo.php';

$pdo = get_pdo_cli();
$env = load_env_file(__DIR__ . '/../.env');

$apiRoot = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';
$rulesPath = $apiRoot . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'rules_data.json';
$questionsPath = $apiRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'adusafe-questions.json';

if (!is_readable($rulesPath)) {
  echo "rules_data.json을 찾을 수 없습니다: {$rulesPath}\n";
  exit(1);
}
if (!is_readable($questionsPath)) {
  echo "adusafe-questions.json을 찾을 수 없습니다: {$questionsPath}\n";
  exit(1);
}

$aduRules = json_decode(file_get_contents($rulesPath), true);
$aduQuestions = json_decode(file_get_contents($questionsPath), true);
if (!is_array($aduRules) || !is_array($aduQuestions)) {
  echo "JSON 파싱 실패.\n";
  exit(1);
}

echo "시드 시작...\n";

try {
  // 1) workspaces 1건
  $stmt = $pdo->query("SELECT workspace_id FROM workspaces WHERE workspace_id = 1");
  if ($stmt->fetch() === false) {
    $pdo->exec("INSERT INTO workspaces (workspace_id, name, plan, status) VALUES (1, '기본 조직', 'free', 'active')");
    echo "  workspaces 1건 추가\n";
  } else {
    echo "  workspaces 이미 존재\n";
  }

  // 2) users 1건
  $stmt = $pdo->query("SELECT user_id FROM users WHERE user_id = 1");
  if ($stmt->fetch() === false) {
    $password = $env['SEED_ADMIN_PASSWORD'] ?? 'Admin123!';
    $hash = hash('sha256', $password);
    $st = $pdo->prepare("INSERT INTO users (user_id, workspace_id, email, password_hash, name, role, status) VALUES (1, 1, 'admin@adsafe.com', ?, '관리자', 'admin', 'active')");
    $st->execute([$hash]);
    echo "  users 1건 추가 (admin@adsafe.com, 비밀번호: SEED_ADMIN_PASSWORD 또는 Admin123!)\n";
  } else {
    echo "  users 이미 존재\n";
  }

  // 3) risk_taxonomy
  $insTax = $pdo->prepare("INSERT INTO risk_taxonomy (risk_code, level_1, level_2, level_3, default_risk_level, description, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
  $selTax = $pdo->prepare("SELECT risk_code FROM risk_taxonomy WHERE risk_code = ?");
  foreach ($aduRules as $rule) {
    $selTax->execute([$rule['riskCode'] ?? '']);
    if ($selTax->fetch() === false) {
      $insTax->execute([
        $rule['riskCode'] ?? '',
        $rule['level1'] ?? '',
        $rule['level2'] ?? '',
        $rule['level3'] ?? '',
        $rule['riskLevel'] ?? 'medium',
        $rule['explanation'] ?? '',
      ]);
    }
  }
  echo "  risk_taxonomy: " . count($aduRules) . "건 반영\n";

  // 4) rule_set_versions + rules
  $stmt = $pdo->query("SELECT rule_set_version_id FROM rule_set_versions WHERE name = 'v1.0.0'");
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row === false) {
    $pdo->exec("INSERT INTO rule_set_versions (name, industry, status, changelog, created_by) VALUES ('v1.0.0', 'medical', 'active', '초기 룰셋 버전 - ADU_RULES 기반 생성', 1)");
    $ruleSetVersionId = (int) $pdo->lastInsertId();
    echo "  rule_set_versions 1건 추가 (v1.0.0)\n";
  } else {
    $ruleSetVersionId = (int) $row['rule_set_version_id'];
    echo "  rule_set_versions 이미 존재 (v1.0.0)\n";
  }

  $insRule = $pdo->prepare("INSERT INTO rules (rule_set_version_id, risk_code, rule_name, rule_type, pattern, severity_override, explanation_template, suggestion_template, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
  $selRule = $pdo->prepare("SELECT rule_id FROM rules WHERE rule_set_version_id = ? AND risk_code = ?");
  $ruleCount = 0;
  foreach ($aduRules as $rule) {
    $selRule->execute([$ruleSetVersionId, $rule['riskCode'] ?? '']);
    if ($selRule->fetch() !== false) continue;

    $keywords = $rule['keywords'] ?? [];
    $regex = $rule['regex'] ?? [];
    $pattern = '';
    if (!empty($keywords)) $pattern = is_array($keywords) ? implode(', ', $keywords) : $keywords;
    if (!empty($regex)) $pattern .= ($pattern ? ' | ' : '') . 'regex: ' . (is_array($regex) ? implode(', ', $regex) : $regex);

    $ruleType = 'keyword';
    if (!empty($regex) && !empty($keywords)) $ruleType = 'combo';
    elseif (!empty($regex)) $ruleType = 'regex';

    $insRule->execute([
      $ruleSetVersionId,
      $rule['riskCode'] ?? '',
      $rule['level3'] ?? $rule['level2'] ?? $rule['riskCode'] ?? '',
      $ruleType,
      $pattern,
      $rule['riskLevel'] ?? 'medium',
      $rule['explanation'] ?? '',
      $rule['suggestion'] ?? '',
    ]);
    $ruleCount++;
  }
  echo "  rules: {$ruleCount}건 추가 (version: {$ruleSetVersionId})\n";

  // 5) quizzes + quiz_choices
  $insQuiz = $pdo->prepare("INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES (1, ?, 'normal', ?, ?, ?, 1)");
  $insChoice = $pdo->prepare("INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES (?, ?, ?, ?)");
  $selQuiz = $pdo->prepare("SELECT quiz_id FROM quizzes WHERE question = ? AND workspace_id = 1");
  $quizCount = 0;
  foreach ($aduQuestions as $q) {
    $stem = $q['stem'] ?? '';
    $selQuiz->execute([$stem]);
    if ($selQuiz->fetch() !== false) continue;

    $insQuiz->execute([
      $q['riskCode'] ?? null,
      $stem,
      $q['explanation'] ?? '',
      $q['suggestion'] ?? '',
    ]);
    $quizId = (int) $pdo->lastInsertId();
    $options = $q['options'] ?? [];
    $correctIndex = (int) ($q['correctIndex'] ?? 0);
    foreach ($options as $i => $text) {
      $insChoice->execute([$quizId, $i, $text, $i === $correctIndex ? 1 : 0]);
    }
    $quizCount++;
  }
  echo "  quizzes + quiz_choices: {$quizCount}건 추가\n";

  echo "시드 완료.\n";
} catch (Throwable $e) {
  echo "시드 실패: " . $e->getMessage() . "\n";
  exit(1);
}
