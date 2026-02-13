<?php
declare(strict_types=1);

/**
 * AduSafe 퀴즈 관리 API (관리자용 CRUD)
 * - GET    /api/admin/quizzes              — 문제 목록 (필터, 페이징)
 * - GET    /api/admin/quizzes/:id          — 문제 상세
 * - POST   /api/admin/quizzes              — 문제 추가
 * - PUT    /api/admin/quizzes/:id          — 문제 수정
 * - DELETE /api/admin/quizzes/:id          — 문제 삭제
 * - PUT    /api/admin/quizzes/:id/toggle   — 활성/비활성 토글
 */

/**
 * GET /api/admin/quizzes — 문제 목록
 */
function handle_admin_get_quizzes(): void {
    try {
        $pdo = get_pdo();

        $workspaceId = isset($_GET['workspace_id']) ? (int)$_GET['workspace_id'] : 1;
        $riskCode = isset($_GET['risk_code']) ? trim($_GET['risk_code']) : '';
        $difficulty = isset($_GET['difficulty']) ? trim($_GET['difficulty']) : '';
        $isActive = isset($_GET['is_active']) ? $_GET['is_active'] : '';
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 20;
        $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
        $offset = ($page - 1) * $limit;

        $where = "q.workspace_id = ?";
        $params = [$workspaceId];

        if ($riskCode !== '') {
            $where .= " AND q.category_risk_code = ?";
            $params[] = $riskCode;
        }
        if ($difficulty !== '') {
            $where .= " AND q.difficulty = ?";
            $params[] = $difficulty;
        }
        if ($isActive !== '') {
            $where .= " AND q.is_active = ?";
            $params[] = (int)$isActive;
        }
        if ($search !== '') {
            $where .= " AND q.question LIKE ?";
            $params[] = '%' . $search . '%';
        }

        // 총 건수
        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM quizzes q WHERE {$where}");
        $stmtC->execute($params);
        $total = (int)$stmtC->fetchColumn();

        // 목록 (LIMIT/OFFSET은 정수로 직접 삽입 — PDO 문자열 바인딩 문제 방지)
        $limitInt = (int)$limit;
        $offsetInt = (int)$offset;
        $stmt = $pdo->prepare("
            SELECT q.quiz_id, q.category_risk_code AS riskCode, q.difficulty, q.question, 
                   q.explanation, q.source_ref AS sourceRef, q.is_active, q.created_at, q.updated_at,
                   rt.level_1, rt.level_2, rt.level_3
            FROM quizzes q
            LEFT JOIN risk_taxonomy rt ON q.category_risk_code = rt.risk_code
            WHERE {$where}
            ORDER BY q.quiz_id DESC
            LIMIT {$limitInt} OFFSET {$offsetInt}
        ");
        $stmt->execute($params);
        $quizzes = $stmt->fetchAll();

        // 각 문제의 보기도 함께 가져오기
        if (!empty($quizzes)) {
            $ids = array_column($quizzes, 'quiz_id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt2 = $pdo->prepare("SELECT quiz_id, choice_id, choice_no, choice_text, is_correct FROM quiz_choices WHERE quiz_id IN ({$placeholders}) ORDER BY quiz_id, choice_no");
            $stmt2->execute($ids);
            $allChoices = $stmt2->fetchAll();

            $choicesByQuiz = [];
            foreach ($allChoices as $c) {
                $choicesByQuiz[$c['quiz_id']][] = $c;
            }
            foreach ($quizzes as &$q) {
                $q['choices'] = $choicesByQuiz[$q['quiz_id']] ?? [];
            }
            unset($q);
        }

        json_response([
            'quizzes' => $quizzes,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    } catch (Throwable $e) {
        json_response(['error' => '문제 목록 조회 실패', 'message' => $e->getMessage()], 500);
    }
}

/**
 * GET /api/admin/quizzes/:id — 문제 상세
 */
function handle_admin_get_quiz(int $quizId): void {
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare("
            SELECT q.*, rt.level_1, rt.level_2, rt.level_3
            FROM quizzes q
            LEFT JOIN risk_taxonomy rt ON q.category_risk_code = rt.risk_code
            WHERE q.quiz_id = ?
        ");
        $stmt->execute([$quizId]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            json_response(['error' => '문제를 찾을 수 없습니다.'], 404);
            return;
        }

        // 보기
        $stmt2 = $pdo->prepare("SELECT choice_id, choice_no, choice_text, is_correct FROM quiz_choices WHERE quiz_id = ? ORDER BY choice_no");
        $stmt2->execute([$quizId]);
        $quiz['choices'] = $stmt2->fetchAll();

        json_response($quiz);
    } catch (Throwable $e) {
        json_response(['error' => '문제 상세 조회 실패', 'message' => $e->getMessage()], 500);
    }
}

/**
 * POST /api/admin/quizzes — 문제 추가
 * Body: { question, riskCode, difficulty, explanation, sourceRef, choices: [{text, isCorrect}...] }
 */
function handle_admin_create_quiz(): void {
    try {
        $body = read_json_body();
        $question = trim((string)($body['question'] ?? ''));
        $riskCode = trim((string)($body['riskCode'] ?? ''));
        $difficulty = trim((string)($body['difficulty'] ?? 'normal'));
        $explanation = trim((string)($body['explanation'] ?? ''));
        $sourceRef = trim((string)($body['sourceRef'] ?? ''));
        $choices = $body['choices'] ?? [];
        $workspaceId = (int)($body['workspace_id'] ?? 1);

        if ($question === '') json_response(['error' => '문제 내용이 필요합니다.'], 400);
        if (empty($choices) || count($choices) < 2) json_response(['error' => '보기가 최소 2개 필요합니다.'], 400);

        // 정답 확인
        $hasCorrect = false;
        foreach ($choices as $c) {
            if (!empty($c['isCorrect'])) $hasCorrect = true;
        }
        if (!$hasCorrect) json_response(['error' => '정답이 최소 1개 필요합니다.'], 400);

        $pdo = get_pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->execute([
                $workspaceId,
                $riskCode ?: null,
                $difficulty,
                $question,
                $explanation ?: null,
                $sourceRef ?: null,
            ]);
            $quizId = (int)$pdo->lastInsertId();

            $stmtC = $pdo->prepare(
                "INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES (?, ?, ?, ?)"
            );
            foreach ($choices as $i => $c) {
                $stmtC->execute([
                    $quizId,
                    $i + 1,
                    trim((string)($c['text'] ?? '')),
                    !empty($c['isCorrect']) ? 1 : 0,
                ]);
            }

            $pdo->commit();
            json_response(['quizId' => $quizId, 'message' => '문제가 추가되었습니다.'], 201);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Throwable $e) {
        json_response(['error' => '문제 추가 실패', 'message' => $e->getMessage()], 500);
    }
}

/**
 * PUT /api/admin/quizzes/:id — 문제 수정
 */
function handle_admin_update_quiz(int $quizId): void {
    try {
        $body = read_json_body();
        $question = trim((string)($body['question'] ?? ''));
        $riskCode = trim((string)($body['riskCode'] ?? ''));
        $difficulty = trim((string)($body['difficulty'] ?? ''));
        $explanation = trim((string)($body['explanation'] ?? ''));
        $sourceRef = trim((string)($body['sourceRef'] ?? ''));
        $choices = $body['choices'] ?? null;

        $pdo = get_pdo();

        // 존재 확인
        $stmt = $pdo->prepare("SELECT quiz_id FROM quizzes WHERE quiz_id = ?");
        $stmt->execute([$quizId]);
        if (!$stmt->fetch()) {
            json_response(['error' => '문제를 찾을 수 없습니다.'], 404);
            return;
        }

        $pdo->beginTransaction();

        try {
            // 문제 업데이트
            $sets = [];
            $params = [];

            if ($question !== '') { $sets[] = "question = ?"; $params[] = $question; }
            if ($riskCode !== '') { $sets[] = "category_risk_code = ?"; $params[] = $riskCode; }
            if ($difficulty !== '') { $sets[] = "difficulty = ?"; $params[] = $difficulty; }
            if ($explanation !== '') { $sets[] = "explanation = ?"; $params[] = $explanation; }
            $sets[] = "source_ref = ?"; $params[] = $sourceRef ?: null;

            if (!empty($sets)) {
                $params[] = $quizId;
                $pdo->prepare("UPDATE quizzes SET " . implode(', ', $sets) . " WHERE quiz_id = ?")->execute($params);
            }

            // 보기 업데이트 (전체 교체)
            if (is_array($choices) && !empty($choices)) {
                $pdo->prepare("DELETE FROM quiz_choices WHERE quiz_id = ?")->execute([$quizId]);
                $stmtC = $pdo->prepare(
                    "INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES (?, ?, ?, ?)"
                );
                foreach ($choices as $i => $c) {
                    $stmtC->execute([
                        $quizId,
                        $i + 1,
                        trim((string)($c['text'] ?? '')),
                        !empty($c['isCorrect']) ? 1 : 0,
                    ]);
                }
            }

            $pdo->commit();
            json_response(['message' => '문제가 수정되었습니다.']);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Throwable $e) {
        json_response(['error' => '문제 수정 실패', 'message' => $e->getMessage()], 500);
    }
}

/**
 * DELETE /api/admin/quizzes/:id — 문제 삭제
 */
function handle_admin_delete_quiz(int $quizId): void {
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE quiz_id = ?");
        $stmt->execute([$quizId]);

        if ($stmt->rowCount() === 0) {
            json_response(['error' => '문제를 찾을 수 없습니다.'], 404);
            return;
        }

        json_response(['message' => '문제가 삭제되었습니다.']);
    } catch (Throwable $e) {
        json_response(['error' => '문제 삭제 실패', 'message' => $e->getMessage()], 500);
    }
}

/**
 * PUT /api/admin/quizzes/:id/toggle — 활성/비활성 토글
 */
function handle_admin_toggle_quiz(int $quizId): void {
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare("SELECT is_active FROM quizzes WHERE quiz_id = ?");
        $stmt->execute([$quizId]);
        $row = $stmt->fetch();

        if (!$row) {
            json_response(['error' => '문제를 찾을 수 없습니다.'], 404);
            return;
        }

        $newActive = $row['is_active'] ? 0 : 1;
        $pdo->prepare("UPDATE quizzes SET is_active = ? WHERE quiz_id = ?")->execute([$newActive, $quizId]);

        json_response([
            'quizId' => $quizId,
            'isActive' => (bool)$newActive,
            'message' => $newActive ? '문제가 활성화되었습니다.' : '문제가 비활성화되었습니다.',
        ]);
    } catch (Throwable $e) {
        json_response(['error' => '토글 실패', 'message' => $e->getMessage()], 500);
    }
}
