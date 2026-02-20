<?php
declare(strict_types=1);

require_once __DIR__ . '/normalize.php';
require_once __DIR__ . '/rules_data.php';

function adsafe_inspect_run(string $rawText): array {
  $normalized = adsafe_normalize($rawText);
  $findings = [];
  $lower = mb_strtolower($normalized, 'UTF-8');

  $isInRawText = function(string $matched) use ($rawText): bool {
    if ($matched === '' || $rawText === '') return false;
    $rawNoSpace = preg_replace('/\s+/u', '', $rawText) ?? $rawText;
    $matchNoSpace = preg_replace('/\s+/u', '', $matched) ?? $matched;
    return (mb_strpos($rawNoSpace, $matchNoSpace, 0, 'UTF-8') !== false) || (mb_strpos($rawText, $matched, 0, 'UTF-8') !== false);
  };

  $addFinding = function(
    string $riskCode,
    string $riskLevel,
    string $matchedText,
    string $explanation,
    string $suggestion,
    ?string $level1,
    ?string $level2,
    ?string $level3,
    bool $skipRawCheck = false,
    ?int $ruleId = null
  ) use (&$findings, $isInRawText): void {
    if (!$skipRawCheck && !$isInRawText($matchedText)) return;
    foreach ($findings as $f) {
      if (($f['riskCode'] ?? '') === $riskCode && ($f['matchedText'] ?? '') === $matchedText) return;
    }
    $findings[] = [
      'riskCode' => $riskCode,
      'riskLevel' => $riskLevel !== '' ? $riskLevel : 'medium',
      'matchedText' => $matchedText,
      'explanation' => $explanation,
      'suggestion' => $suggestion,
      'level1' => $level1,
      'level2' => $level2,
      'level3' => $level3,
      'rule_id' => $ruleId,
    ];
  };

  $rulesData = adsafe_rules();
  $rules = isset($rulesData['rules']) ? $rulesData['rules'] : $rulesData;
  $ruleSetVersionId = $rulesData['rule_set_version_id'] ?? null;

  foreach ($rules as $rule) {
    $ruleType = $rule['rule_type'] ?? null;
    $ruleId = isset($rule['rule_id']) ? (int)$rule['rule_id'] : null;
    $keywords = $rule['keywords'] ?? [];
    $regexList = $rule['regex'] ?? [];
    $condition = $rule['condition'] ?? [];

    if ($ruleType === 'numeric') {
      $extractRegex = $condition['extract_regex'] ?? '';
      $threshold = (int)($condition['threshold'] ?? 0);
      $thresholdOp = $condition['threshold_op'] ?? '>=';
      $matchGroup = (int)($condition['match_group'] ?? 1);
      $matchedLabel = $condition['matched_label'] ?? '';
      if ($extractRegex !== '') {
        $pat = '/' . str_replace('/', '\/', $extractRegex) . '/iu';
        $m = [];
        if (@preg_match_all($pat, $normalized, $m) && isset($m[$matchGroup])) {
          foreach ($m[0] as $i => $full) {
            $num = isset($m[$matchGroup][$i]) ? (int)$m[$matchGroup][$i] : 0;
            $ok = $thresholdOp === '>=' ? ($num >= $threshold) : ($num > $threshold);
            if ($ok) {
              $addFinding(
                (string)$rule['riskCode'],
                (string)($rule['riskLevel'] ?? 'medium'),
                trim((string)$full),
                (string)($rule['explanation'] ?? ''),
                (string)($rule['suggestion'] ?? ''),
                $rule['level1'] ?? null,
                $rule['level2'] ?? null,
                $rule['level3'] ?? $matchedLabel,
                false,
                $ruleId
              );
            }
          }
        }
      }
      continue;
    }

    if ($ruleType === 'combo') {
      $reqKw = $condition['require_keywords'] ?? [];
      $forbidRegex = $condition['forbid_regex'] ?? null;
      $optCondKw = $condition['optional_condition_keywords'] ?? [];
      $optPeriodRegex = $condition['optional_period_regex'] ?? null;
      $matchedLabel = $condition['matched_label'] ?? '';
      $levelIfNoPeriod = $condition['level_if_no_period'] ?? 'high';
      $levelIfCondition = $condition['level_if_condition'] ?? 'medium';

      $hasRequire = false;
      if (is_array($reqKw)) {
        foreach ($reqKw as $kw) {
          if (preg_match('/\b' . preg_quote((string)$kw, '/') . '\b/iu', $lower) === 1) { $hasRequire = true; break; }
        }
      }
      if (!$hasRequire) { continue; }

      if ($forbidRegex !== null && $forbidRegex !== '') {
        $hasForbid = @preg_match('/' . str_replace('/', '\/', $forbidRegex) . '/iu', $normalized) === 1;
        $alreadyExcessive = false;
        foreach ($findings as $f) {
          if (($f['riskCode'] ?? '') === 'RISK_PRICE_EXCESSIVE') { $alreadyExcessive = true; break; }
        }
        if (!$hasForbid && !$alreadyExcessive) {
          $addFinding(
            (string)$rule['riskCode'],
            (string)($rule['riskLevel'] ?? 'medium'),
            $matchedLabel,
            (string)($rule['explanation'] ?? ''),
            (string)($rule['suggestion'] ?? ''),
            $rule['level1'] ?? null,
            $rule['level2'] ?? null,
            $rule['level3'] ?? $matchedLabel,
            true,
            $ruleId
          );
        }
        continue;
      }

      if ($optPeriodRegex !== null && $optPeriodRegex !== '') {
        $hasPeriod = @preg_match('/' . str_replace('/', '\/', $optPeriodRegex) . '/iu', $normalized) === 1;
        $hasCond = false;
        if (is_array($optCondKw)) {
          foreach ($optCondKw as $kw) {
            if (preg_match('/\b' . preg_quote((string)$kw, '/') . '\b/iu', $lower) === 1) { $hasCond = true; break; }
          }
        }
        if ($hasCond || !$hasPeriod) {
          $alreadyInduce = false;
          foreach ($findings as $f) {
            if (($f['riskCode'] ?? '') === 'RISK_INDUCEMENT_CONDITION') { $alreadyInduce = true; break; }
          }
          if (!$alreadyInduce) {
            $level = !$hasPeriod ? $levelIfNoPeriod : $levelIfCondition;
            $condText = $matchedLabel;
            if ($hasCond && preg_match('/\b(선착순|한정|오늘만|당첨자|후기\s*조건)\b/iu', $lower, $mCond)) {
              $condText = $mCond[0];
            } elseif ($hasCond) {
              $condText = '조건부 혜택';
            }
            $addFinding(
              (string)$rule['riskCode'],
              $level,
              $condText,
              (string)($rule['explanation'] ?? ''),
              (string)($rule['suggestion'] ?? ''),
              $rule['level1'] ?? null,
              $rule['level2'] ?? null,
              $rule['level3'] ?? $matchedLabel,
              true,
              $ruleId
            );
          }
        }
      }
      continue;
    }

    foreach ($keywords as $kw) {
      $kw = (string)$kw;
      if ($kw === '') continue;
      $search = mb_strtolower($kw, 'UTF-8');
      $idx = mb_strpos($lower, $search, 0, 'UTF-8');
      if ($idx === false) continue;
      $matchedText = mb_substr($normalized, (int)$idx, mb_strlen($kw, 'UTF-8'), 'UTF-8');
      $addFinding(
        (string)$rule['riskCode'],
        (string)($rule['riskLevel'] ?? 'medium'),
        $matchedText,
        (string)($rule['explanation'] ?? ''),
        (string)($rule['suggestion'] ?? ''),
        $rule['level1'] ?? null,
        $rule['level2'] ?? null,
        $rule['level3'] ?? null,
        false,
        $ruleId
      );
    }

    $regexList = $rule['regex'] ?? [];
    foreach ($regexList as $rStr) {
      $rStr = (string)$rStr;
      if ($rStr === '') continue;
      // JS 'gi'에 대응: PHP는 i + u, global은 preg_match_all
      $pattern = '/' . str_replace('/', '\/', $rStr) . '/iu';
      $matches = [];
      $ok = @preg_match_all($pattern, $normalized, $matches);
      if ($ok === false || $ok === 0) continue;
      foreach ($matches[0] as $m) {
        $matchedText = (string)$m;
        $addFinding(
          (string)$rule['riskCode'],
          (string)($rule['riskLevel'] ?? 'medium'),
          $matchedText,
          (string)($rule['explanation'] ?? ''),
          (string)($rule['suggestion'] ?? ''),
          $rule['level1'] ?? null,
          $rule['level2'] ?? null,
          $rule['level3'] ?? null,
          false,
          $ruleId
        );
      }
    }
  }

  // (이벤트 자동 체크는 DB numeric/combo 룰로 이전됨 — 아래 블록 미사용)
  $_dead = preg_match('/\b(이벤트|할인|특가|프로모션)\b/iu', $lower) === 1;
  $_unused_hasCondition = preg_match('/\b(선착순|한정|오늘만|당첨자|후기\s*조건)\b/iu', $lower) === 1;
  $_unused_hasPeriod = preg_match('/\b(\d{4}\s*[.\-\/]\s*\d{1,2}|\d{1,2}\s*월|\d{1,2}\s*일\s*까지|~.*까지|기간|종료)\b/iu', $lower) === 1;
  if (false && $_dead && ($_unused_hasCondition || !$_unused_hasPeriod)) {
    $condText = '이벤트 기간 미표기';
    if ($_unused_hasCondition && preg_match('/\b(선착순|한정|오늘만|당첨자|후기\s*조건)\b/iu', $lower, $mCond)) {
      $condText = $mCond[0];
    } elseif ($_unused_hasCondition) {
      $condText = '조건부 혜택';
    }
    $already = false;
    foreach ($findings as $f) {
      if (($f['riskCode'] ?? '') === 'RISK_INDUCEMENT_CONDITION') { $already = true; break; }
    }
    if (!$already) {
      $level = !$_unused_hasPeriod ? 'high' : ($_unused_hasCondition ? 'medium' : 'low');
      $ex = !$hasPeriod ? '이벤트가 있으나 기간(시작/종료)이 표기되지 않았습니다.' : '이벤트와 조건부 혜택이 함께 사용되었습니다.';
      $addFinding('RISK_INDUCEMENT_CONDITION', $level, $condText, $ex, '이벤트 기간과 적용 대상(시술/진료)을 명시하세요.', '유인', '조건', '이벤트 기간 미기재', true);
    }
  }

  $level = 'none';
  if (count($findings) > 0) {
    $hasHigh = false; $hasMed = false;
    foreach ($findings as $f) {
      $rl = $f['riskLevel'] ?? 'medium';
      if ($rl === 'high') { $hasHigh = true; break; }
      if ($rl === 'medium') $hasMed = true;
    }
    $level = $hasHigh ? 'high' : ($hasMed ? 'medium' : 'low');
  }

  return [
    'summary' => [
      'level' => $level,
      'totalFindings' => count($findings),
      'message' => count($findings) === 0 ? '리스크 신호가 감지되지 않았습니다.' : '리스크 신호가 감지되었습니다.',
    ],
    'findings' => $findings,
    'rawText' => $rawText,
    'normalizedText' => $normalized,
    'rule_set_version_id' => $ruleSetVersionId,
  ];
}

