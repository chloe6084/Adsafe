-- 이벤트 자동 체크 룰 3건 (활성 룰셋에만 INSERT, 중복 방지)
-- 실행: mysql -u user -p dbname < seed_event_rules.sql 또는 DB 클라이언트에서 실행

-- (1) 할인 50% 이상 — numeric
INSERT INTO rules (rule_set_version_id, risk_code, rule_name, rule_type, pattern, condition_json, severity_override, explanation_template, suggestion_template, is_active)
SELECT
  rsv.rule_set_version_id,
  'RISK_PRICE_EXCESSIVE',
  '할인 50% 이상',
  'numeric',
  'event_numeric_50',
  (JSON_OBJECT(
    'extract_regex', '(\\d+)\\s*%\\s*(?:이상\\s*)?(할인|이벤트|오프)',
    'threshold', 50,
    'threshold_op', '>=',
    'match_group', 1,
    'matched_label', '50% 이상'
  )),
  'high',
  '할인율 50% 이상은 고위험으로 제한될 수 있습니다.',
  '할인율을 50% 미만으로 하거나 조건·기간을 명확히 하세요.',
  1
FROM rule_set_versions rsv
WHERE rsv.status = 'active'
  AND NOT EXISTS (SELECT 1 FROM rules r WHERE r.rule_set_version_id = rsv.rule_set_version_id AND r.pattern = 'event_numeric_50')
LIMIT 1;

-- (2) 할인율 미기재 — combo
INSERT INTO rules (rule_set_version_id, risk_code, rule_name, rule_type, pattern, condition_json, severity_override, explanation_template, suggestion_template, is_active)
SELECT
  rsv.rule_set_version_id,
  'RISK_PRICE_EXCESSIVE',
  '할인율 미기재',
  'combo',
  'event_combo_discount_missing',
  (JSON_OBJECT(
    'require_keywords', JSON_ARRAY('할인', '이벤트', '특가', '반값', '무료'),
    'require_keywords_match', 'any',
    'forbid_regex', '\\d+\\s*%\\s*(할인|이벤트|오프)',
    'matched_label', '할인율 미기재'
  )),
  'medium',
  '할인 표현이 있으나 할인율이 명시되지 않았습니다.',
  '할인율과 이벤트 기간을 명시하세요.',
  1
FROM rule_set_versions rsv
WHERE rsv.status = 'active'
  AND NOT EXISTS (SELECT 1 FROM rules r WHERE r.rule_set_version_id = rsv.rule_set_version_id AND r.pattern = 'event_combo_discount_missing')
LIMIT 1;

-- (3) 이벤트 기간/조건부 — combo
INSERT INTO rules (rule_set_version_id, risk_code, rule_name, rule_type, pattern, condition_json, severity_override, explanation_template, suggestion_template, is_active)
SELECT
  rsv.rule_set_version_id,
  'RISK_INDUCEMENT_CONDITION',
  '이벤트 기간 미표기',
  'combo',
  'event_combo_period',
  (JSON_OBJECT(
    'require_keywords', JSON_ARRAY('이벤트', '할인', '특가', '프로모션'),
    'require_keywords_match', 'any',
    'optional_condition_keywords', JSON_ARRAY('선착순', '한정', '오늘만', '당첨자', '후기 조건'),
    'optional_period_regex', '\\b(\\d{4}\\s*[.-/]\\s*\\d{1,2}|\\d{1,2}\\s*월|\\d{1,2}\\s*일\\s*까지|~.*까지|기간|종료)\\b',
    'matched_label', '이벤트 기간 미표기',
    'level_if_no_period', 'high',
    'level_if_condition', 'medium'
  )),
  'medium',
  '이벤트가 있으나 기간(시작/종료)이 표기되지 않았습니다.',
  '이벤트 기간과 적용 대상(시술/진료)을 명시하세요.',
  1
FROM rule_set_versions rsv
WHERE rsv.status = 'active'
  AND NOT EXISTS (SELECT 1 FROM rules r WHERE r.rule_set_version_id = rsv.rule_set_version_id AND r.pattern = 'event_combo_period')
LIMIT 1;
