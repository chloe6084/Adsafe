<?php
declare(strict_types=1);

/**
 * 룰 관리 API
 * - GET /api/rules - 룰 목록 조회
 * - POST /api/rules - 룰 추가
 * - PUT /api/rules/:id - 룰 수정
 * - GET /api/rule-set-versions - 룰셋 버전 목록
 * - POST /api/rule-set-versions - 룰셋 버전 추가
 */

// 룰셋 버전 목록 조회
function handle_get_rule_set_versions(): void {
  try {
    $pdo = get_pdo();
    $stmt = $pdo->query("
      SELECT 
        rule_set_version_id AS id,
        name AS versionName,
        industry,
        status,
        changelog,
        created_at AS createdAt,
        activated_at AS activatedAt
      FROM rule_set_versions
      ORDER BY 
        CASE status WHEN 'active' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END,
        created_at DESC
    ");
    $items = $stmt->fetchAll();

    json_response(['items' => $items, 'total' => count($items)]);
  } catch (Throwable $e) {
    json_response(['error' => '룰셋 버전 조회 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

// 룰셋 버전 삭제
function handle_delete_rule_set_version(int $versionId): void {
  try {
    $pdo = get_pdo();

    // 존재 확인
    $stmt = $pdo->prepare("SELECT rule_set_version_id, name, status FROM rule_set_versions WHERE rule_set_version_id = ?");
    $stmt->execute([$versionId]);
    $version = $stmt->fetch();
    
    if (!$version) {
      json_response(['error' => '룰셋 버전을 찾을 수 없습니다.'], 404);
      return;
    }

    // 활성 버전은 삭제 불가
    if ($version['status'] === 'active') {
      json_response(['error' => '활성 상태의 룰셋 버전은 삭제할 수 없습니다. 먼저 비활성화하세요.'], 400);
      return;
    }

    // 연결된 룰 개수 확인
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM rules WHERE rule_set_version_id = ?");
    $stmt->execute([$versionId]);
    $ruleCount = (int)$stmt->fetch()['cnt'];

    // 연결된 룰이 있으면 함께 삭제 (CASCADE 설정되어 있지만 명시적으로 알림)
    if ($ruleCount > 0) {
      // CASCADE 삭제됨
    }

    // 삭제
    $stmt = $pdo->prepare("DELETE FROM rule_set_versions WHERE rule_set_version_id = ?");
    $stmt->execute([$versionId]);

    json_response([
      'message' => '룰셋 버전이 삭제되었습니다.',
      'deletedRules' => $ruleCount
    ]);
  } catch (Throwable $e) {
    json_response(['error' => '룰셋 버전 삭제 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

// 룰셋 버전 추가
function handle_create_rule_set_version(): void {
  try {
    $body = read_json_body();
    
    $name = isset($body['versionName']) ? trim((string)$body['versionName']) : '';
    $industry = isset($body['industry']) ? trim((string)$body['industry']) : 'general';
    $status = isset($body['status']) ? trim((string)$body['status']) : 'draft';
    $changelog = isset($body['changelog']) ? trim((string)$body['changelog']) : '';

    if ($name === '') {
      json_response(['error' => '버전명을 입력하세요.'], 400);
      return;
    }

    if (!in_array($industry, ['medical', 'health_supplement', 'general', 'other'], true)) {
      $industry = 'general';
    }
    if (!in_array($status, ['draft', 'active', 'deprecated'], true)) {
      $status = 'draft';
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare("
      INSERT INTO rule_set_versions (name, industry, status, changelog)
      VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$name, $industry, $status, $changelog]);
    $id = (int)$pdo->lastInsertId();

    json_response([
      'message' => '룰셋 버전이 추가되었습니다.',
      'item' => [
        'id' => $id,
        'versionName' => $name,
        'industry' => $industry,
        'status' => $status,
        'changelog' => $changelog
      ]
    ], 201);
  } catch (Throwable $e) {
    json_response(['error' => '룰셋 버전 추가 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

// 룰셋 버전 활성화
function handle_activate_rule_set_version(int $versionId): void {
  try {
    $pdo = get_pdo();

    // 버전 존재 확인
    $stmt = $pdo->prepare("SELECT rule_set_version_id, status FROM rule_set_versions WHERE rule_set_version_id = ?");
    $stmt->execute([$versionId]);
    $version = $stmt->fetch();

    if (!$version) {
      json_response(['error' => '룰셋 버전을 찾을 수 없습니다.'], 404);
      return;
    }

    if ($version['status'] === 'active') {
      json_response(['error' => '이미 활성화된 버전입니다.'], 400);
      return;
    }

    // 트랜잭션 시작
    $pdo->beginTransaction();

    try {
      // 기존 활성 버전을 비활성화
      $stmt = $pdo->prepare("UPDATE rule_set_versions SET status = 'inactive', activated_at = NULL WHERE status = 'active'");
      $stmt->execute();

      // 선택한 버전 활성화
      $stmt = $pdo->prepare("UPDATE rule_set_versions SET status = 'active', activated_at = NOW() WHERE rule_set_version_id = ?");
      $stmt->execute([$versionId]);

      $pdo->commit();

      json_response(['message' => '룰셋 버전이 활성화되었습니다.']);
    } catch (Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
  } catch (Throwable $e) {
    json_response(['error' => '룰셋 버전 활성화 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

// 룰셋 버전 비활성화
function handle_deactivate_rule_set_version(int $versionId): void {
  try {
    $pdo = get_pdo();

    // 버전 존재 확인
    $stmt = $pdo->prepare("SELECT rule_set_version_id, status FROM rule_set_versions WHERE rule_set_version_id = ?");
    $stmt->execute([$versionId]);
    $version = $stmt->fetch();

    if (!$version) {
      json_response(['error' => '룰셋 버전을 찾을 수 없습니다.'], 404);
      return;
    }

    if ($version['status'] !== 'active') {
      json_response(['error' => '활성 상태가 아닌 버전입니다.'], 400);
      return;
    }

    // 비활성화
    $stmt = $pdo->prepare("UPDATE rule_set_versions SET status = 'inactive', activated_at = NULL WHERE rule_set_version_id = ?");
    $stmt->execute([$versionId]);

    json_response(['message' => '룰셋 버전이 비활성화되었습니다.']);
  } catch (Throwable $e) {
    json_response(['error' => '룰셋 버전 비활성화 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

// 룰 목록 조회
function handle_get_rules(): void {
  try {
    $pdo = get_pdo();
    
    // 쿼리 파라미터
    $versionId = isset($_GET['version_id']) ? (int)$_GET['version_id'] : null;
    $riskCode = isset($_GET['risk_code']) ? trim($_GET['risk_code']) : '';
    $ruleType = isset($_GET['rule_type']) ? trim($_GET['rule_type']) : '';
    $severity = isset($_GET['severity']) ? trim($_GET['severity']) : '';

    $sql = "
      SELECT 
        r.rule_id AS id,
        r.rule_set_version_id AS versionId,
        r.risk_code AS riskCode,
        r.rule_name AS ruleName,
        r.rule_type AS ruleType,
        r.pattern,
        r.condition_json AS conditionJson,
        r.severity_override AS severity,
        r.explanation_template AS explanation,
        r.suggestion_template AS suggestion,
        r.is_active AS isActive,
        r.created_at AS createdAt,
        t.level_1 AS level1,
        t.level_2 AS level2,
        t.level_3 AS level3,
        t.default_risk_level AS defaultRiskLevel
      FROM rules r
      LEFT JOIN risk_taxonomy t ON r.risk_code = t.risk_code
      WHERE 1=1
    ";
    $params = [];

    if ($versionId) {
      $sql .= " AND r.rule_set_version_id = ?";
      $params[] = $versionId;
    }
    if ($riskCode !== '') {
      $sql .= " AND r.risk_code = ?";
      $params[] = $riskCode;
    }
    if ($ruleType !== '' && in_array($ruleType, ['keyword', 'regex', 'numeric', 'combo'], true)) {
      $sql .= " AND r.rule_type = ?";
      $params[] = $ruleType;
    }
    if ($severity !== '' && in_array($severity, ['low', 'medium', 'high'], true)) {
      $sql .= " AND (r.severity_override = ? OR (r.severity_override IS NULL AND t.default_risk_level = ?))";
      $params[] = $severity;
      $params[] = $severity;
    }

    $sql .= " ORDER BY r.rule_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    $result = array_map(function($row) {
      return [
        'id' => (int)$row['id'],
        'versionId' => (int)$row['versionId'],
        'riskCode' => $row['riskCode'],
        'ruleName' => $row['ruleName'],
        'ruleType' => $row['ruleType'],
        'pattern' => $row['pattern'],
        'conditionJson' => $row['conditionJson'],
        'severity' => $row['severity'] ?: $row['defaultRiskLevel'],
        'explanation' => $row['explanation'],
        'suggestion' => $row['suggestion'],
        'isActive' => (bool)$row['isActive'],
        'level1' => $row['level1'],
        'level2' => $row['level2'],
        'level3' => $row['level3']
      ];
    }, $items);

    json_response(['items' => $result, 'total' => count($result)]);
  } catch (Throwable $e) {
    json_response(['error' => '룰 목록 조회 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

// 룰 추가
function handle_create_rule(): void {
  try {
    $body = read_json_body();
    
    $versionId = isset($body['versionId']) ? (int)$body['versionId'] : 0;
    $riskCode = isset($body['riskCode']) ? trim((string)$body['riskCode']) : '';
    $ruleName = isset($body['ruleName']) ? trim((string)$body['ruleName']) : '';
    $ruleType = isset($body['ruleType']) ? trim((string)$body['ruleType']) : 'keyword';
    $pattern = isset($body['pattern']) ? trim((string)$body['pattern']) : '';
    $severity = isset($body['severity']) ? trim((string)$body['severity']) : null;
    $explanation = isset($body['explanation']) ? trim((string)$body['explanation']) : '';
    $isActive = isset($body['isActive']) ? (bool)$body['isActive'] : true;

    // 유효성 검사
    if ($riskCode === '') {
      json_response(['error' => '리스크 코드를 선택하세요.'], 400);
      return;
    }
    if (!in_array($ruleType, ['keyword', 'regex', 'numeric', 'combo'], true)) {
      $ruleType = 'keyword';
    }
    if ($severity !== null && !in_array($severity, ['low', 'medium', 'high'], true)) {
      $severity = null;
    }

    $pdo = get_pdo();

    // 룰셋 버전이 없으면 활성 버전 사용 또는 생성
    if ($versionId <= 0) {
      $stmt = $pdo->query("SELECT rule_set_version_id FROM rule_set_versions WHERE status = 'active' LIMIT 1");
      $row = $stmt->fetch();
      if ($row) {
        $versionId = (int)$row['rule_set_version_id'];
      } else {
        // 기본 버전 생성
        $stmt = $pdo->prepare("INSERT INTO rule_set_versions (name, status) VALUES ('v1.0.0', 'active')");
        $stmt->execute();
        $versionId = (int)$pdo->lastInsertId();
      }
    }

    // 룰 추가
    $stmt = $pdo->prepare("
      INSERT INTO rules (rule_set_version_id, risk_code, rule_name, rule_type, pattern, severity_override, explanation_template, is_active)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$versionId, $riskCode, $ruleName, $ruleType, $pattern, $severity, $explanation, $isActive ? 1 : 0]);
    $ruleId = (int)$pdo->lastInsertId();

    json_response([
      'message' => '룰이 추가되었습니다.',
      'item' => [
        'id' => $ruleId,
        'versionId' => $versionId,
        'riskCode' => $riskCode,
        'ruleName' => $ruleName,
        'ruleType' => $ruleType,
        'pattern' => $pattern,
        'severity' => $severity,
        'explanation' => $explanation,
        'isActive' => $isActive
      ]
    ], 201);
  } catch (Throwable $e) {
    $errorMsg = $e->getMessage();
    // 외래키 에러 안내
    if (strpos($errorMsg, 'foreign key') !== false || strpos($errorMsg, 'FOREIGN KEY') !== false) {
      if (strpos($errorMsg, 'risk_code') !== false) {
        json_response(['error' => '존재하지 않는 리스크 코드입니다. 먼저 택소노미를 추가하세요.', 'detail' => $errorMsg], 400);
      } else {
        json_response(['error' => 'DB 관계 오류가 발생했습니다.', 'detail' => $errorMsg], 500);
      }
    } else {
      json_response(['error' => '룰 추가 중 오류가 발생했습니다.', 'message' => $errorMsg], 500);
    }
  }
}

// 룰 삭제
function handle_delete_rule(int $ruleId): void {
  try {
    $pdo = get_pdo();

    // 존재 확인
    $stmt = $pdo->prepare("SELECT rule_id, rule_name FROM rules WHERE rule_id = ?");
    $stmt->execute([$ruleId]);
    $rule = $stmt->fetch();
    
    if (!$rule) {
      json_response(['error' => '룰을 찾을 수 없습니다.'], 404);
      return;
    }

    // 삭제
    $stmt = $pdo->prepare("DELETE FROM rules WHERE rule_id = ?");
    $stmt->execute([$ruleId]);

    json_response(['message' => '룰이 삭제되었습니다.']);
  } catch (Throwable $e) {
    json_response(['error' => '룰 삭제 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}

// 룰 수정
function handle_update_rule(int $ruleId): void {
  try {
    $body = read_json_body();

    $pdo = get_pdo();

    // 존재 확인
    $stmt = $pdo->prepare("SELECT rule_id FROM rules WHERE rule_id = ?");
    $stmt->execute([$ruleId]);
    if (!$stmt->fetch()) {
      json_response(['error' => '룰을 찾을 수 없습니다.'], 404);
      return;
    }

    // 업데이트 필드 구성
    $updates = [];
    $params = [];

    if (isset($body['ruleName'])) {
      $updates[] = 'rule_name = ?';
      $params[] = trim((string)$body['ruleName']);
    }
    if (isset($body['ruleType'])) {
      $type = trim((string)$body['ruleType']);
      if (in_array($type, ['keyword', 'regex', 'numeric', 'combo'], true)) {
        $updates[] = 'rule_type = ?';
        $params[] = $type;
      }
    }
    if (isset($body['pattern'])) {
      $updates[] = 'pattern = ?';
      $params[] = trim((string)$body['pattern']);
    }
    if (isset($body['severity'])) {
      $sev = trim((string)$body['severity']);
      if (in_array($sev, ['low', 'medium', 'high'], true)) {
        $updates[] = 'severity_override = ?';
        $params[] = $sev;
      }
    }
    if (isset($body['explanation'])) {
      $updates[] = 'explanation_template = ?';
      $params[] = trim((string)$body['explanation']);
    }
    if (isset($body['suggestion'])) {
      $updates[] = 'suggestion_template = ?';
      $params[] = trim((string)$body['suggestion']);
    }
    if (isset($body['isActive'])) {
      $updates[] = 'is_active = ?';
      $params[] = $body['isActive'] ? 1 : 0;
    }

    if (empty($updates)) {
      json_response(['error' => '수정할 항목이 없습니다.'], 400);
      return;
    }

    $params[] = $ruleId;
    $sql = "UPDATE rules SET " . implode(', ', $updates) . " WHERE rule_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(['message' => '룰이 수정되었습니다.']);
  } catch (Throwable $e) {
    json_response(['error' => '룰 수정 중 오류가 발생했습니다.', 'message' => $e->getMessage()], 500);
  }
}
