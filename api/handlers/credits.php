<?php
declare(strict_types=1);

/**
 * 크레딧 시스템 API
 * - GET  /api/credits/balance?user_id=N      — 잔액 조회
 * - GET  /api/credits/plans                   — 요금제 목록
 * - POST /api/credits/check                   — 기능 사용 가능 여부 확인
 * - POST /api/credits/use                     — 크레딧 차감 (기능 사용)
 * - POST /api/credits/grant                   — 관리자: 크레딧 부여
 * - PUT  /api/credits/plan                    — 관리자: 사용자 플랜 변경
 * - GET  /api/credits/transactions?user_id=N  — 거래 이력
 */

// ─── 헬퍼 ────────────────────────────────────────────

/**
 * 사용자 크레딧 레코드를 보장 (없으면 생성, 일일 리셋 처리)
 */
function ensure_user_credits(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT * FROM user_credits WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row) {
        // 사용자의 role 확인 → admin/owner면 admin 플랜
        $stmtU = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmtU->execute([$userId]);
        $user = $stmtU->fetch();
        $planCode = 'free';
        if ($user && in_array($user['role'], ['admin', 'owner'])) {
            $planCode = 'admin';
        }

        $pdo->prepare(
            "INSERT INTO user_credits (user_id, plan_code, credit_balance, daily_inspect_used, daily_quiz_used, daily_ai_used, last_daily_reset)
             VALUES (?, ?, 0, 0, 0, 0, CURDATE())"
        )->execute([$userId, $planCode]);

        $stmt->execute([$userId]);
        $row = $stmt->fetch();
    }

    // 일일 리셋 체크
    $today = date('Y-m-d');
    if (!$row['last_daily_reset'] || $row['last_daily_reset'] !== $today) {
        $pdo->prepare(
            "UPDATE user_credits SET daily_inspect_used = 0, daily_quiz_used = 0, daily_ai_used = 0, last_daily_reset = CURDATE() WHERE user_id = ?"
        )->execute([$userId]);
        $row['daily_inspect_used'] = 0;
        $row['daily_quiz_used'] = 0;
        $row['daily_ai_used'] = 0;
        $row['last_daily_reset'] = $today;
    }

    return $row;
}

/**
 * 요금제 한도 조회
 */
function get_plan_limits(PDO $pdo, string $planCode): array {
    $stmt = $pdo->prepare("SELECT * FROM credit_plans WHERE plan_code = ? AND is_active = 1");
    $stmt->execute([$planCode]);
    $plan = $stmt->fetch();
    if (!$plan) {
        // fallback to free
        $stmt->execute(['free']);
        $plan = $stmt->fetch();
    }
    return $plan ?: [
        'daily_inspect_limit' => 5,
        'daily_quiz_limit' => 3,
        'daily_ai_generate_limit' => 0,
        'history_view_limit' => 10,
    ];
}

/**
 * 기능 사용 가능 여부 확인
 */
function check_feature_available(PDO $pdo, int $userId, string $feature): array {
    $credits = ensure_user_credits($pdo, $userId);
    $plan = get_plan_limits($pdo, $credits['plan_code']);

    $limitKey = '';
    $usedKey = '';
    $needsCredit = false;

    switch ($feature) {
        case 'inspect':
            $limitKey = 'daily_inspect_limit';
            $usedKey = 'daily_inspect_used';
            break;
        case 'quiz':
            $limitKey = 'daily_quiz_limit';
            $usedKey = 'daily_quiz_used';
            break;
        case 'ai_generate':
            $limitKey = 'daily_ai_generate_limit';
            $usedKey = 'daily_ai_used';
            $needsCredit = true;
            break;
        default:
            return ['available' => true, 'reason' => ''];
    }

    $limit = (int)($plan[$limitKey] ?? 0);
    $used = (int)($credits[$usedKey] ?? 0);

    // -1 = 무제한
    if ($limit === -1) {
        return [
            'available' => true,
            'limit' => -1,
            'used' => $used,
            'remaining' => -1,
            'plan' => $credits['plan_code'],
        ];
    }

    if ($used >= $limit) {
        return [
            'available' => false,
            'reason' => '일일 사용 한도에 도달했습니다. (한도: ' . $limit . '회)',
            'limit' => $limit,
            'used' => $used,
            'remaining' => 0,
            'plan' => $credits['plan_code'],
        ];
    }

    return [
        'available' => true,
        'limit' => $limit,
        'used' => $used,
        'remaining' => $limit - $used,
        'plan' => $credits['plan_code'],
    ];
}

// ─── 핸들러 ────────────────────────────────────────────

function handle_get_credit_balance(): void {
    try {
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($userId <= 0) json_response(['error' => 'user_id가 필요합니다.'], 400);

        $pdo = get_pdo();
        $credits = ensure_user_credits($pdo, $userId);
        $plan = get_plan_limits($pdo, $credits['plan_code']);

        json_response([
            'userId' => $userId,
            'planCode' => $credits['plan_code'],
            'planName' => $plan['plan_name'] ?? $credits['plan_code'],
            'creditBalance' => (int)$credits['credit_balance'],
            'daily' => [
                'inspect' => [
                    'used' => (int)$credits['daily_inspect_used'],
                    'limit' => (int)$plan['daily_inspect_limit'],
                ],
                'quiz' => [
                    'used' => (int)$credits['daily_quiz_used'],
                    'limit' => (int)$plan['daily_quiz_limit'],
                ],
                'aiGenerate' => [
                    'used' => (int)$credits['daily_ai_used'],
                    'limit' => (int)$plan['daily_ai_generate_limit'],
                ],
            ],
            'historyViewLimit' => (int)$plan['history_view_limit'],
        ]);
    } catch (Throwable $e) {
        json_response(['error' => '크레딧 조회 실패', 'message' => $e->getMessage()], 500);
    }
}

function handle_get_credit_plans(): void {
    try {
        $pdo = get_pdo();
        $stmt = $pdo->query("SELECT * FROM credit_plans WHERE is_active = 1 ORDER BY price_monthly ASC");
        $plans = $stmt->fetchAll();
        json_response(['plans' => $plans]);
    } catch (Throwable $e) {
        json_response(['error' => '요금제 조회 실패', 'message' => $e->getMessage()], 500);
    }
}

function handle_check_credit(): void {
    try {
        $body = read_json_body();
        $userId = (int)($body['user_id'] ?? 0);
        $feature = trim((string)($body['feature'] ?? ''));

        if ($userId <= 0) json_response(['error' => 'user_id가 필요합니다.'], 400);
        if ($feature === '') json_response(['error' => 'feature가 필요합니다.'], 400);

        $pdo = get_pdo();
        $result = check_feature_available($pdo, $userId, $feature);
        json_response($result);
    } catch (Throwable $e) {
        json_response(['error' => '확인 실패', 'message' => $e->getMessage()], 500);
    }
}

function handle_use_credit(): void {
    try {
        $body = read_json_body();
        $userId = (int)($body['user_id'] ?? 0);
        $feature = trim((string)($body['feature'] ?? ''));
        $referenceId = trim((string)($body['reference_id'] ?? ''));

        if ($userId <= 0) json_response(['error' => 'user_id가 필요합니다.'], 400);
        if ($feature === '') json_response(['error' => 'feature가 필요합니다.'], 400);

        $pdo = get_pdo();

        // 사용 가능 확인
        $check = check_feature_available($pdo, $userId, $feature);
        if (!$check['available']) {
            json_response(['error' => $check['reason'], 'available' => false], 403);
            return;
        }

        // 일일 카운터 증가
        $colMap = [
            'inspect' => 'daily_inspect_used',
            'quiz' => 'daily_quiz_used',
            'ai_generate' => 'daily_ai_used',
        ];
        $col = $colMap[$feature] ?? null;
        if ($col) {
            $pdo->prepare("UPDATE user_credits SET {$col} = {$col} + 1 WHERE user_id = ?")->execute([$userId]);
        }

        // 거래 이력 기록
        $credits = ensure_user_credits($pdo, $userId);
        $pdo->prepare(
            "INSERT INTO credit_transactions (user_id, type, amount, balance_after, description, feature, reference_id)
             VALUES (?, 'use', -1, ?, ?, ?, ?)"
        )->execute([
            $userId,
            (int)$credits['credit_balance'],
            $feature . ' 사용',
            $feature,
            $referenceId ?: null,
        ]);

        json_response(['success' => true, 'message' => '사용 기록 완료']);
    } catch (Throwable $e) {
        json_response(['error' => '크레딧 차감 실패', 'message' => $e->getMessage()], 500);
    }
}

function handle_grant_credit(): void {
    try {
        $body = read_json_body();
        $targetUserId = (int)($body['target_user_id'] ?? 0);
        $amount = (int)($body['amount'] ?? 0);
        $adminUserId = (int)($body['admin_user_id'] ?? 0);
        $description = trim((string)($body['description'] ?? '관리자 크레딧 부여'));

        if ($targetUserId <= 0) json_response(['error' => 'target_user_id가 필요합니다.'], 400);
        if ($amount <= 0) json_response(['error' => '양수 amount가 필요합니다.'], 400);

        $pdo = get_pdo();
        $credits = ensure_user_credits($pdo, $targetUserId);

        $newBalance = (int)$credits['credit_balance'] + $amount;
        $pdo->prepare("UPDATE user_credits SET credit_balance = ? WHERE user_id = ?")->execute([$newBalance, $targetUserId]);

        $pdo->prepare(
            "INSERT INTO credit_transactions (user_id, type, amount, balance_after, description, feature, created_by)
             VALUES (?, 'admin_grant', ?, ?, ?, 'credit', ?)"
        )->execute([$targetUserId, $amount, $newBalance, $description, $adminUserId ?: null]);

        json_response(['success' => true, 'newBalance' => $newBalance]);
    } catch (Throwable $e) {
        json_response(['error' => '크레딧 부여 실패', 'message' => $e->getMessage()], 500);
    }
}

function handle_change_plan(): void {
    try {
        $body = read_json_body();
        $targetUserId = (int)($body['target_user_id'] ?? 0);
        $newPlanCode = trim((string)($body['plan_code'] ?? ''));
        $adminUserId = (int)($body['admin_user_id'] ?? 0);

        if ($targetUserId <= 0) json_response(['error' => 'target_user_id가 필요합니다.'], 400);
        if ($newPlanCode === '') json_response(['error' => 'plan_code가 필요합니다.'], 400);

        $pdo = get_pdo();

        // 요금제 존재 확인
        $stmt = $pdo->prepare("SELECT plan_code FROM credit_plans WHERE plan_code = ? AND is_active = 1");
        $stmt->execute([$newPlanCode]);
        if (!$stmt->fetch()) {
            json_response(['error' => '존재하지 않는 요금제입니다.'], 400);
            return;
        }

        $credits = ensure_user_credits($pdo, $targetUserId);
        $oldPlan = $credits['plan_code'];

        $pdo->prepare("UPDATE user_credits SET plan_code = ? WHERE user_id = ?")->execute([$newPlanCode, $targetUserId]);

        $pdo->prepare(
            "INSERT INTO credit_transactions (user_id, type, amount, balance_after, description, feature, created_by)
             VALUES (?, 'plan_change', 0, ?, ?, 'plan', ?)"
        )->execute([
            $targetUserId,
            (int)$credits['credit_balance'],
            "플랜 변경: {$oldPlan} → {$newPlanCode}",
            $adminUserId ?: null,
        ]);

        json_response(['success' => true, 'oldPlan' => $oldPlan, 'newPlan' => $newPlanCode]);
    } catch (Throwable $e) {
        json_response(['error' => '플랜 변경 실패', 'message' => $e->getMessage()], 500);
    }
}

function handle_get_credit_transactions(): void {
    try {
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 20;
        $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
        $offset = ($page - 1) * $limit;

        if ($userId <= 0) json_response(['error' => 'user_id가 필요합니다.'], 400);

        $pdo = get_pdo();
        $limitInt = (int)$limit;
        $offsetInt = (int)$offset;
        $stmt = $pdo->prepare(
            "SELECT * FROM credit_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limitInt} OFFSET {$offsetInt}"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM credit_transactions WHERE user_id = ?");
        $stmtC->execute([$userId]);
        $total = (int)$stmtC->fetchColumn();

        json_response([
            'transactions' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    } catch (Throwable $e) {
        json_response(['error' => '거래 이력 조회 실패', 'message' => $e->getMessage()], 500);
    }
}
