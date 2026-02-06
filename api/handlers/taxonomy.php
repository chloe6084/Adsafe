<?php
declare(strict_types=1);

/**
 * 리스크 택소노미 API
 * - GET /api/taxonomy - 목록 조회
 * - POST /api/taxonomy - 추가
 * - PUT /api/taxonomy/:code - 수정
 */

// 택소노미 목록 조회
function handle_get_taxonomy(): void {
  try {
    $pdo = get_pdo();
    $stmt = $pdo->query("
      SELECT 
        risk_code AS riskCode,
        level_1 AS level1,
        level_2 AS level2,
        level_3 AS level3,
        default_risk_level AS riskLevel,
        description,
        is_active AS isActive,
        created_at AS createdAt,
        updated_at AS updatedAt
      FROM risk_taxonomy
      ORDER BY level_1, level_2, level_3
    ");
    $items = $stmt->fetchAll();

    $result = array_map(function($row) {
      return [
        'riskCode' => $row['riskCode'],
        'level1' => $row['level1'],
        'level2' => $row['level2'],
        'level3' => $row['level3'],
        'riskLevel' => $row['riskLevel'],
        'description' => $row['description'],
        'isActive' => (bool)$row['isActive'],
        'createdAt' => $row['createdAt'],
        'updatedAt' => $row['updatedAt']
      ];
    }, $items);

    json_response(['items' => $result, 'total' => count($result)]);
  } catch (Throwable $e) {
    json_response(['error' => '택소노미 목록 조회 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

// 택소노미 추가
function handle_create_taxonomy(): void {
  try {
    $body = read_json_body();
    
    $riskCode = isset($body['riskCode']) ? trim((string)$body['riskCode']) : '';
    $level1 = isset($body['level1']) ? trim((string)$body['level1']) : '';
    $level2 = isset($body['level2']) ? trim((string)$body['level2']) : '';
    $level3 = isset($body['level3']) ? trim((string)$body['level3']) : '';
    $riskLevel = isset($body['riskLevel']) ? trim((string)$body['riskLevel']) : 'medium';
    $description = isset($body['description']) ? trim((string)$body['description']) : '';
    $isActive = isset($body['isActive']) ? (bool)$body['isActive'] : true;

    // 유효성 검사
    if ($riskCode === '') {
      json_response(['error' => '리스크 코드를 입력하세요.'], 400);
      return;
    }
    if (!preg_match('/^RISK_[A-Z0-9_]+$/', $riskCode)) {
      json_response(['error' => '리스크 코드는 RISK_로 시작하고 대문자, 숫자, 언더스코어만 사용할 수 있습니다.'], 400);
      return;
    }
    if (!in_array($riskLevel, ['low', 'medium', 'high'], true)) {
      $riskLevel = 'medium';
    }

    $pdo = get_pdo();

    // 중복 체크
    $stmt = $pdo->prepare("SELECT risk_code FROM risk_taxonomy WHERE risk_code = ?");
    $stmt->execute([$riskCode]);
    if ($stmt->fetch()) {
      json_response(['error' => '이미 존재하는 리스크 코드입니다.'], 409);
      return;
    }

    // 추가
    $stmt = $pdo->prepare("
      INSERT INTO risk_taxonomy (risk_code, level_1, level_2, level_3, default_risk_level, description, is_active)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$riskCode, $level1, $level2, $level3, $riskLevel, $description, $isActive ? 1 : 0]);

    json_response([
      'message' => '택소노미가 추가되었습니다.',
      'item' => [
        'riskCode' => $riskCode,
        'level1' => $level1,
        'level2' => $level2,
        'level3' => $level3,
        'riskLevel' => $riskLevel,
        'description' => $description,
        'isActive' => $isActive
      ]
    ], 201);
  } catch (Throwable $e) {
    json_response(['error' => '택소노미 추가 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

// 택소노미 삭제
function handle_delete_taxonomy(string $riskCode): void {
  try {
    $pdo = get_pdo();

    // 존재 확인
    $stmt = $pdo->prepare("SELECT risk_code FROM risk_taxonomy WHERE risk_code = ?");
    $stmt->execute([$riskCode]);
    if (!$stmt->fetch()) {
      json_response(['error' => '리스크 유형을 찾을 수 없습니다.'], 404);
      return;
    }

    // 연결된 룰 확인
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM rules WHERE risk_code = ?");
    $stmt->execute([$riskCode]);
    $ruleCount = (int)$stmt->fetch()['cnt'];

    if ($ruleCount > 0) {
      json_response([
        'error' => '이 리스크 유형에 연결된 룰이 ' . $ruleCount . '개 있습니다. 먼저 룰을 삭제하세요.'
      ], 400);
      return;
    }

    // 삭제
    $stmt = $pdo->prepare("DELETE FROM risk_taxonomy WHERE risk_code = ?");
    $stmt->execute([$riskCode]);

    json_response(['message' => '리스크 유형이 삭제되었습니다.']);
  } catch (Throwable $e) {
    json_response(['error' => '리스크 유형 삭제 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

// 택소노미 수정
function handle_update_taxonomy(string $riskCode): void {
  try {
    $body = read_json_body();

    $pdo = get_pdo();

    // 존재 확인
    $stmt = $pdo->prepare("SELECT risk_code FROM risk_taxonomy WHERE risk_code = ?");
    $stmt->execute([$riskCode]);
    if (!$stmt->fetch()) {
      json_response(['error' => '택소노미를 찾을 수 없습니다.'], 404);
      return;
    }

    // 업데이트 필드 구성
    $updates = [];
    $params = [];

    if (isset($body['level1'])) {
      $updates[] = 'level_1 = ?';
      $params[] = trim((string)$body['level1']);
    }
    if (isset($body['level2'])) {
      $updates[] = 'level_2 = ?';
      $params[] = trim((string)$body['level2']);
    }
    if (isset($body['level3'])) {
      $updates[] = 'level_3 = ?';
      $params[] = trim((string)$body['level3']);
    }
    if (isset($body['riskLevel'])) {
      $level = trim((string)$body['riskLevel']);
      if (in_array($level, ['low', 'medium', 'high'], true)) {
        $updates[] = 'default_risk_level = ?';
        $params[] = $level;
      }
    }
    if (isset($body['description'])) {
      $updates[] = 'description = ?';
      $params[] = trim((string)$body['description']);
    }
    if (isset($body['isActive'])) {
      $updates[] = 'is_active = ?';
      $params[] = $body['isActive'] ? 1 : 0;
    }

    if (empty($updates)) {
      json_response(['error' => '수정할 항목이 없습니다.'], 400);
      return;
    }

    $params[] = $riskCode;
    $sql = "UPDATE risk_taxonomy SET " . implode(', ', $updates) . " WHERE risk_code = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(['message' => '택소노미가 수정되었습니다.']);
  } catch (Throwable $e) {
    json_response(['error' => '택소노미 수정 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}
