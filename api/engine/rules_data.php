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
          r.risk_code AS riskCode,
          r.rule_name AS ruleName,
          r.rule_type AS ruleType,
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
        $rules = [];
        foreach ($dbRules as $row) {
          // pattern을 keywords와 regex로 파싱
          $pattern = $row['pattern'] ?? '';
          $keywords = [];
          $regex = [];
          
          if ($pattern !== '') {
            // "regex: " 부분 분리
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
              // 전부 키워드
              $keywords = array_map('trim', explode(',', $pattern));
            }
          }
          
          // 빈 값 제거
          $keywords = array_filter($keywords, fn($k) => $k !== '');
          $regex = array_filter($regex, fn($r) => $r !== '');
          
          $rules[] = [
            'riskCode' => $row['riskCode'],
            'level1' => $row['level1'],
            'level2' => $row['level2'],
            'level3' => $row['level3'],
            'riskLevel' => $row['riskLevel'],
            'keywords' => array_values($keywords),
            'regex' => array_values($regex),
            'explanation' => $row['explanation'],
            'suggestion' => $row['suggestion'],
          ];
        }
        return $rules;
      }
    }
  } catch (Throwable $e) {
    // DB 오류 시 fallback으로 진행
    error_log('adsafe_rules DB error: ' . $e->getMessage());
  }

  // 2) Fallback: 정적 JSON 파일
  $path = __DIR__ . '/rules_data.json';
  if (!file_exists($path)) {
    $rules = [];
    return $rules;
  }
  $raw = file_get_contents($path);
  if ($raw === false) {
    $rules = [];
    return $rules;
  }
  $decoded = json_decode($raw, true);
  $rules = is_array($decoded) ? $decoded : [];
  return $rules;
}

