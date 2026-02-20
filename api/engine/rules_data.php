<?php
declare(strict_types=1);

/**
 * DB에서 활성 룰셋 버전의 룰을 가져옵니다.
 * DB 연결 실패 시 정적 JSON 파일을 fallback으로 사용합니다.
 */
function adsafe_rules(): array {
  static $rules = null;
  if (is_array($rules)) return $rules;

  // 1) DB에서 활성 룰셋의 룰 가져오기 시도
  try {
    $pdo = get_pdo();
    
    // 활성 룰셋 버전 ID 찾기
    $stmt = $pdo->query("SELECT rule_set_version_id FROM rule_set_versions WHERE status = 'active' LIMIT 1");
    $activeVersion = $stmt->fetch();
    
    if ($activeVersion) {
      $versionId = (int)$activeVersion['rule_set_version_id'];
      
      // 해당 버전의 룰 + 택소노미 정보 조인
      $stmt = $pdo->prepare("
        SELECT 
          r.rule_id,
          r.rule_type AS ruleType,
          r.condition_json,
          r.risk_code AS riskCode,
          r.rule_name AS ruleName,
          r.pattern,
          COALESCE(r.severity_override, t.default_risk_level, 'medium') AS riskLevel,
          COALESCE(r.explanation_template, t.description, '') AS explanation,
          COALESCE(r.suggestion_template, '') AS suggestion,
          t.level_1 AS level1,
          t.level_2 AS level2,
          t.level_3 AS level3
        FROM rules r
        LEFT JOIN risk_taxonomy t ON r.risk_code = t.risk_code
        WHERE r.rule_set_version_id = ? AND r.is_active = 1
      ");
      $stmt->execute([$versionId]);
      $dbRules = $stmt->fetchAll();
      
      if (!empty($dbRules)) {
        $list = [];
        foreach ($dbRules as $row) {
          $ruleType = $row['ruleType'] ?? 'keyword';
          $ruleId = isset($row['rule_id']) ? (int)$row['rule_id'] : null;
          $base = [
            'rule_id' => $ruleId,
            'riskCode' => $row['riskCode'],
            'level1' => $row['level1'],
            'level2' => $row['level2'],
            'level3' => $row['level3'],
            'riskLevel' => $row['riskLevel'],
            'explanation' => $row['explanation'],
            'suggestion' => $row['suggestion'],
          ];
          if ($ruleType === 'numeric' || $ruleType === 'combo') {
            $condJson = $row['condition_json'] ?? null;
            $condition = is_string($condJson) ? json_decode($condJson, true) : $condJson;
            $base['rule_type'] = $ruleType;
            $base['condition'] = is_array($condition) ? $condition : [];
            $base['keywords'] = [];
            $base['regex'] = [];
            $list[] = $base;
            continue;
          }
          // keyword / regex: pattern을 keywords와 regex로 파싱
          $pattern = $row['pattern'] ?? '';
          $keywords = [];
          $regex = [];
          if ($pattern !== '') {
            if (strpos($pattern, 'regex:') !== false) {
              $parts = explode('|', $pattern);
              foreach ($parts as $part) {
                $part = trim($part);
                if (strpos($part, 'regex:') === 0) {
                  $regexStr = trim(substr($part, 6));
                  $regex = array_map('trim', explode(',', $regexStr));
                } else {
                  $keywords = array_merge($keywords, array_map('trim', explode(',', $part)));
                }
              }
            } else {
              $keywords = array_map('trim', explode(',', $pattern));
            }
          }
          $keywords = array_values(array_filter($keywords, fn($k) => $k !== ''));
          $regex = array_values(array_filter($regex, fn($r) => $r !== ''));
          $base['keywords'] = $keywords;
          $base['regex'] = $regex;
          $list[] = $base;
        }
        $rules = ['rule_set_version_id' => $versionId, 'rules' => $list];
        return $rules;
      }
    }
  } catch (Throwable $e) {
    // DB 오류 시 fallback으로 진행
    error_log('adsafe_rules DB error: ' . $e->getMessage());
  }

  // 2) Fallback: 정적 JSON 파일 (rule_set_version_id 없음)
  $path = __DIR__ . '/rules_data.json';
  if (!file_exists($path)) {
    $rules = ['rule_set_version_id' => null, 'rules' => []];
    return $rules;
  }
  $raw = file_get_contents($path);
  if ($raw === false) {
    $rules = ['rule_set_version_id' => null, 'rules' => []];
    return $rules;
  }
  $decoded = json_decode($raw, true);
  $list = is_array($decoded) ? $decoded : [];
  // fallback 룰에 rule_id 없음; keyword/regex 형태만 가정
  foreach ($list as $i => $r) {
    if (!isset($r['rule_id'])) $list[$i]['rule_id'] = null;
  }
  $rules = ['rule_set_version_id' => null, 'rules' => $list];
  return $rules;
}

