<?php
declare(strict_types=1);
/**
 * AdSafe DB 시드 (PHP)
 * 실행: php scripts/seed.php (api 폴더에서)
 *   또는 c:\xampp2\php\php.exe scripts/seed.php
 *
 * workspaces 1건, users 1건, risk_taxonomy, rule_set_versions + rules, quizzes + quiz_choices
 */

require_once __DIR__ . '/../lib/bootstrap.php';

// rules_data.json 로드
$rulesJsonPath = __DIR__ . '/../engine/rules_data.json';
$ADU_RULES = [];
if (file_exists($rulesJsonPath)) {
    $raw = file_get_contents($rulesJsonPath);
    $decoded = json_decode($raw ?: '', true);
    if (is_array($decoded)) $ADU_RULES = $decoded;
}

// adusafe-questions.js 파싱 (JS 배열을 JSON으로 변환)
$ADU_QUESTIONS = [];
$questionsJsPath = __DIR__ . '/../../js/adusafe-questions.js';
if (file_exists($questionsJsPath)) {
    $jsContent = file_get_contents($questionsJsPath);
    if ($jsContent !== false) {
        // window.ADU_QUESTIONS = [ ... ]; 에서 배열 부분만 추출
        if (preg_match('/ADU_QUESTIONS\s*=\s*(\[.+\])\s*;?\s*$/s', $jsContent, $m)) {
            $jsonStr = $m[1];
            // JS 키를 JSON 키로 변환: riskCode: → "riskCode":
            $jsonStr = preg_replace('/(\b\w+)\s*:/m', '"$1":', $jsonStr);
            // 작은따옴표를 큰따옴표로 (문자열 값)
            $jsonStr = preg_replace("/'/", '"', $jsonStr);
            // 후행 쉼표 제거
            $jsonStr = preg_replace('/,\s*([\]}])/s', '$1', $jsonStr);
            $parsed = json_decode($jsonStr, true);
            if (is_array($parsed)) $ADU_QUESTIONS = $parsed;
        }
    }
}

echo "시드 시작...\n";

try {
    $pdo = get_pdo();

    // 1) workspaces
    $stmt = $pdo->query('SELECT workspace_id FROM workspaces WHERE workspace_id = 1');
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO workspaces (workspace_id, name, plan, status) VALUES (1, '기본 조직', 'free', 'active')");
        echo "  workspaces 1건 추가\n";
    } else {
        echo "  workspaces 이미 존재\n";
    }

    // 2) users (관리자)
    $stmt = $pdo->query('SELECT user_id FROM users WHERE user_id = 1');
    $env = load_env_file(__DIR__ . '/../.env');
    $defaultPassword = $env['SEED_ADMIN_PASSWORD'] ?? 'Admin123!';
    $passwordHash = hash('sha256', $defaultPassword);
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare("INSERT INTO users (user_id, workspace_id, email, password_hash, name, role, status)
                              VALUES (1, 1, 'admin@adsafe.com', ?, '관리자', 'admin', 'active')");
        $ins->execute([$passwordHash]);
        echo "  users 1건 추가 (admin@adsafe.com)\n";
    } else {
        echo "  users 이미 존재\n";
    }

    // 3) risk_taxonomy
    $checkTax = $pdo->prepare('SELECT risk_code FROM risk_taxonomy WHERE risk_code = ?');
    $insTax = $pdo->prepare("INSERT INTO risk_taxonomy (risk_code, level_1, level_2, level_3, default_risk_level, description, is_active)
                              VALUES (?, ?, ?, ?, ?, ?, 1)");
    foreach ($ADU_RULES as $rule) {
        $checkTax->execute([$rule['riskCode']]);
        if (!$checkTax->fetch()) {
            $insTax->execute([
                $rule['riskCode'],
                $rule['level1'] ?? '',
                $rule['level2'] ?? '',
                $rule['level3'] ?? '',
                $rule['riskLevel'] ?? 'medium',
                $rule['explanation'] ?? '',
            ]);
        }
    }
    echo "  risk_taxonomy: " . count($ADU_RULES) . "건 반영\n";

    // 4) rule_set_versions + rules
    $stmt = $pdo->query("SELECT rule_set_version_id FROM rule_set_versions WHERE name = 'v1.0.0'");
    $existing = $stmt->fetch();
    if (!$existing) {
        $pdo->exec("INSERT INTO rule_set_versions (name, industry, status, changelog, created_by)
                     VALUES ('v1.0.0', 'medical', 'active', '초기 룰셋 버전 - 시드 생성', 1)");
        $ruleSetVersionId = (int) $pdo->lastInsertId();
        echo "  rule_set_versions 1건 추가 (v1.0.0)\n";
    } else {
        $ruleSetVersionId = (int) $existing['rule_set_version_id'];
        echo "  rule_set_versions 이미 존재 (v1.0.0)\n";
    }

    $checkRule = $pdo->prepare('SELECT rule_id FROM rules WHERE rule_set_version_id = ? AND risk_code = ?');
    $insRule = $pdo->prepare("INSERT INTO rules (rule_set_version_id, risk_code, rule_name, rule_type, pattern, severity_override, explanation_template, suggestion_template, is_active)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $ruleCount = 0;
    foreach ($ADU_RULES as $rule) {
        $checkRule->execute([$ruleSetVersionId, $rule['riskCode']]);
        if ($checkRule->fetch()) continue;

        $pattern = '';
        $keywords = $rule['keywords'] ?? [];
        $regex = $rule['regex'] ?? [];
        if (!empty($keywords)) $pattern = implode(', ', $keywords);
        if (!empty($regex)) $pattern .= ($pattern ? ' | ' : '') . 'regex: ' . implode(', ', $regex);

        $ruleType = 'keyword';
        if (!empty($regex) && !empty($keywords)) $ruleType = 'combo';
        elseif (!empty($regex)) $ruleType = 'regex';

        $insRule->execute([
            $ruleSetVersionId,
            $rule['riskCode'],
            $rule['level3'] ?? $rule['level2'] ?? $rule['riskCode'],
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
    $checkQuiz = $pdo->prepare('SELECT quiz_id FROM quizzes WHERE question = ? AND workspace_id = 1');
    $insQuiz = $pdo->prepare("INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active)
                               VALUES (1, ?, 'normal', ?, ?, ?, 1)");
    $insChoice = $pdo->prepare("INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES (?, ?, ?, ?)");
    $quizCount = 0;
    foreach ($ADU_QUESTIONS as $q) {
        $stem = $q['stem'] ?? '';
        if ($stem === '') continue;
        $checkQuiz->execute([$stem]);
        if ($checkQuiz->fetch()) continue;

        $insQuiz->execute([
            $q['riskCode'] ?? null,
            $stem,
            $q['explanation'] ?? '',
            $q['suggestion'] ?? '',
        ]);
        $quizId = (int) $pdo->lastInsertId();

        $options = $q['options'] ?? [];
        $correctIdx = $q['correctIndex'] ?? -1;
        foreach ($options as $i => $opt) {
            $insChoice->execute([$quizId, $i, $opt, ($i === $correctIdx) ? 1 : 0]);
        }
        $quizCount++;
    }
    echo "  quizzes + quiz_choices: {$quizCount}건 추가\n";

    echo "시드 완료.\n";
} catch (Throwable $e) {
    echo "시드 실패: " . $e->getMessage() . "\n";
    exit(1);
}
