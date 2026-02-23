-- 의료법 룰 엔진 고도화: 신규 택소노미 4종 + 룰 추가
-- 실행: MySQL 클라이언트로 adsafe_2(또는 실제 DB) 접속 후 source 로 실행.
-- 로컬에 adsafe_2 없으면 Aiven 등 원격 DB에 연결해 실행하세요.

SET NAMES utf8mb4;
USE adsafe_2;

-- 1) 신규 risk_taxonomy 4종
INSERT INTO risk_taxonomy (risk_code, level_1, level_2, level_3, default_risk_level, description, is_active) VALUES
('RISK_FALSE_NUMERIC', '거짓표시', '허위수치', '근거 없는 수치/효과', 'high', '극단 수치(0%/100%/99.9%)와 성과지표(재발률·만족도 등) 결합 시 검증이 필요할 수 있습니다.', 1),
('RISK_UNVERIFIED_CREDENTIAL', '거짓표시', '자격인증', '근거 없는 인증/공인', 'high', '공식·공인·인증 등과 전문의/병원/기관 결합 시 근거(기관명·인증번호) 제시가 필요할 수 있습니다.', 1),
('RISK_UNVERIFIED_EQUIPMENT', '거짓표시', '장비주장', '최초/유일/승인 주장', 'high', '국내 유일·전국 최초·FDA/CE 승인 등 장비 관련 주장은 검증이 필요할 수 있습니다.', 1),
('RISK_PRICE_DECEPTIVE', '가격', '기만소지', '조건 생략/단정', 'high', '추가 비용 없음·전 품목 할인 등 조건이 불명확하면 오인 소지가 있을 수 있습니다.', 1)
ON DUPLICATE KEY UPDATE
  level_1 = VALUES(level_1),
  level_2 = VALUES(level_2),
  level_3 = VALUES(level_3),
  description = VALUES(description),
  updated_at = NOW();

-- 2) 활성 룰셋 버전에 신규 룰 추가 (keyword/regex 패턴)
-- pattern: 키워드는 쉼표 구분, regex는 "regex: 패턴1, 패턴2" 형태로 rules_data.php가 파싱함

INSERT INTO rules (rule_set_version_id, risk_code, rule_name, rule_type, pattern, severity_override, explanation_template, suggestion_template, is_active)
SELECT rsv.rule_set_version_id, 'RISK_FALSE_NUMERIC', '근거 없는 수치·효과(근접)', 'regex',
  'regex: (재발률|만족도|성공률|부작용률|효과|완치|개선률).{0,25}(0|100|99\\.9)\\s*%, (0|100|99\\.9)\\s*%.{0,25}(재발률|만족도|성공률|부작용률|효과|완치)',
  'high',
  '극단 수치(0%/100%/99.9%)와 성과지표가 함께 사용되었습니다. 근거 제시가 필요할 수 있습니다.',
  '통계·효과 수치는 출처와 조사 방법을 명시하거나, 단정 표현을 완화하세요.',
  1
FROM rule_set_versions rsv WHERE rsv.status = 'active' LIMIT 1;

INSERT INTO rules (rule_set_version_id, risk_code, rule_name, rule_type, pattern, severity_override, explanation_template, suggestion_template, is_active)
SELECT rsv.rule_set_version_id, 'RISK_UNVERIFIED_CREDENTIAL', '근거 없는 자격·인증', 'keyword',
  '공식 인증, 공인, 인증 병원, 지정 병원, 수상, 우수 기관, 국가 공인, 정부 지정',
  'high',
  '자격·인증·지정 표현이 있습니다. 인증 기관명·인증번호·연도 등 근거 제시가 필요할 수 있습니다.',
  '인증·자격은 취득 기관·번호·연도를 함께 명시하세요.',
  1
FROM rule_set_versions rsv WHERE rsv.status = 'active' LIMIT 1;

INSERT INTO rules (rule_set_version_id, risk_code, rule_name, rule_type, pattern, severity_override, explanation_template, suggestion_template, is_active)
SELECT rsv.rule_set_version_id, 'RISK_UNVERIFIED_EQUIPMENT', '장비·최초/유일/승인 주장', 'keyword',
  '국내 유일, 전국 최초, FDA 승인, CE 인증, KFDA 승인, 식약처 승인, 안전 보장, 정품 100%',
  'high',
  '장비·기술의 유일성·승인 주장이 있습니다. 검증이 필요할 수 있습니다.',
  '최초·유일·승인 주장은 인증서·특허 등 근거를 명시하세요.',
  1
FROM rule_set_versions rsv WHERE rsv.status = 'active' LIMIT 1;

INSERT INTO rules (rule_set_version_id, risk_code, rule_name, rule_type, pattern, severity_override, explanation_template, suggestion_template, is_active)
SELECT rsv.rule_set_version_id, 'RISK_PRICE_DECEPTIVE', '가격·조건 기만 소지', 'keyword',
  '추가 비용 없음, 전혀 없음, 별도 비용 없음, 전 품목 할인, 모든 시술, 무제한, 정가 대비',
  'high',
  '추가 비용 없음·전 품목 할인 등 단정 표현이 있습니다. 적용 대상·기간·부가비용 조건을 명시하는 것이 좋습니다.',
  '적용 대상(시술/진료), 기간, 부가비용(마취·약 등) 안내를 함께 표기하세요.',
  1
FROM rule_set_versions rsv WHERE rsv.status = 'active' LIMIT 1;

SELECT 'Migration: risk_taxonomy 4 + rules 4 added.' AS message;
