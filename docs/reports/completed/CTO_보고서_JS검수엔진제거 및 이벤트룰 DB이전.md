# CTO 팀 기술 보고서

---

| 항목 | 내용 |
|------|------|
| **문서번호** | CTO-RPT-ADSAFE-2025-001 |
| **제목** | AdSafe 검수 엔진 정리 및 이벤트 룰 DB 이전 |
| **작성일** | 2025년 2월 13일 |
| **작성팀** | CTO (Cursor Tech / Product) |
| **관련 프로젝트** | AdSafe MVP |
| **관련 플랜** | JS 검수 엔진 제거 (WP0) |

---

## 1. 개요

AdSafe 검수 기능의 **진입점 단일화** 및 **룰 관리 일원화**를 위해, 브라우저 측 JS 검수 엔진을 제거하고 서버(PHP)만 검수 수행하도록 정리하였으며, 이벤트 자동 체크(할인 50% 이상, 할인율 미기재, 이벤트 기간/조건부) 로직을 DB 룰(numeric/combo)로 이전하였다.

---

## 2. 배경 및 목적

### 2.1 배경

- 검수 실행은 실제로 **PHP API(`POST /api/inspect`)** 한 경로만 사용되고 있으나, 브라우저에 검수 엔진·룰 데이터 스크립트가 잔존하여 이중 유지보수 및 표시 불일치 가능성이 있음.
- 이벤트 자동 체크 로직이 PHP / Node / 브라우저 JS **3곳에 하드코딩**되어 있어, 룰 변경 시 코드 배포가 필요하고 이력에서 "어떤 룰로 적발했는지" 추적이 불가한 상태였음.

### 2.2 목적

- **Phase A (WP0)**  
  - 브라우저 JS 검수 엔진·룰 데이터 제거로 프론트 부담 감소.  
  - 검수 결과 표시를 **API 응답(level3 등) 기준**으로 통일.
- **Phase B**  
  - 이벤트 자동 체크를 **DB `rules` 테이블(numeric/combo + condition_json)** 로 이전.  
  - 검수 이력에 **rule_set_version_id**, **rule_id** 저장하여 감사·분석 가능하도록 함.

---

## 3. 작업 범위

| 구분 | 범위 | 비고 |
|------|------|------|
| **Phase A** | 브라우저 전용 JS 파일 삭제, HTML script 제거, main.js 표시 로직 수정 | 플랜 §7 Phase A |
| **Phase B** | rules_data.php 확장, 이벤트 룰 3건 INSERT, inspection_engine.php numeric/combo 처리, inspect.php 저장 필드 반영 | 플랜 §7 Phase B |
| **유지** | api/lib (Node 검수 엔진), rules_data.json fallback, js/rule-set-versions.js 등 | 플랜 §2-3 |

---

## 4. 수행 내용

### 4.1 Phase A — 브라우저 JS 검수 엔진 제거 (WP0)

| 단계 | 작업 | 상세 |
|------|------|------|
| A-1 | 파일 삭제 | `js/inspection-engine.js`, `js/rules-data.js`, `js/normalize.js` 3건 삭제 |
| A-2 | adsafe.html | 위 3개 스크립트 로드 `<script>` 3줄 제거 |
| A-3 | inspection-history.html | `js/rules-data.js` `<script>` 1줄 제거 |
| A-4 | inspection-detail.html | `js/rules-data.js` `<script>` 1줄 제거 |
| A-5 | main.js | (1) finding 표시 시 **finding.level3** 우선, 없으면 getRiskCodeName(riskCode) (2) getRiskCodeName에서 **window.ADU_RULES** 분기 제거, fallback 맵만 사용 |

**결과**: 검수 호출은 기존과 동일하게 `POST /api/inspect`만 사용되며, 리스크 이름은 API가 내려주는 level3 또는 클라이언트 fallback 맵으로만 표시됨.

---

### 4.2 Phase B — 이벤트 자동 체크 DB 이전

#### 4.2.1 룰 로드·파싱 확장 (rules_data.php)

- **SELECT 확장**: `r.rule_id`, `r.rule_type`, `r.condition_json` 추가.
- **반환 구조 변경**:  
  - 기존: 룰 배열만 반환.  
  - 변경: `['rule_set_version_id' => (int|null), 'rules' => [...]]` 반환.
- **numeric / combo 룰**:  
  - `condition_json`을 `json_decode`하여 `'condition'` 키로 넘김.  
  - `rule_id`, `rule_type` 포함.
- **Fallback(JSON)**: 동일 구조로 `rule_set_version_id => null`, `rules => [...]` 반환.

#### 4.2.2 이벤트 룰 3건 INSERT

- **파일**: `docs/sql/seed_event_rules.sql` 신규 작성.
- **내용**: 활성 룰셋(`status = 'active'`)에 대해 아래 3건 INSERT (중복 시 스킵).
  1. **할인 50% 이상** — rule_type=`numeric`, risk_code=`RISK_PRICE_EXCESSIVE`, condition_json(numeric_threshold).
  2. **할인율 미기재** — rule_type=`combo`, risk_code=`RISK_PRICE_EXCESSIVE`, condition_json(require_keywords, forbid_regex).
  3. **이벤트 기간/조건부** — rule_type=`combo`, risk_code=`RISK_INDUCEMENT_CONDITION`, condition_json(optional_period_regex, optional_condition_keywords 등).

#### 4.2.3 검수 엔진 수정 (inspection_engine.php)

- **addFinding**: 10번째 인자 `rule_id`(nullable int) 추가, finding 배열에 `rule_id` 포함.
- **룰 소스**: `adsafe_rules()`의 새 반환 구조 사용. `rule_set_version_id` 보관.
- **rule_type 분기**  
  - **numeric**: condition의 extract_regex, threshold, threshold_op, match_group으로 숫자 추출·비교 후 조건 만족 시 addFinding(…, rule_id).  
  - **combo (할인율 미기재)**: require_keywords 존재 + forbid_regex 미매칭 + 기존 RISK_PRICE_EXCESSIVE 없을 때 finding 추가.  
  - **combo (이벤트 기간/조건)**: require_keywords + (조건 키워드 존재 또는 기간 패턴 없음) 시 level 분기하여 finding 추가.  
  - **keyword/regex**: 기존 로직 유지, addFinding 호출 시 rule_id 전달.
- **반환**: `rule_set_version_id` 포함.
- **기존 이벤트 하드코딩 블록**: 실행되지 않도록 비활성화 처리(실제 삭제는 인코딩 이슈로 별도 진행 가능).

#### 4.2.4 검수 저장 반영 (inspect.php)

- **inspection_runs**: INSERT 시 `rule_set_version_id` 컬럼에 `$result['rule_set_version_id']` 사용.
- **inspection_findings**: INSERT 시 `rule_id` 컬럼에 각 finding의 `rule_id` 사용(없으면 NULL).

---

## 5. 결과 및 검증

### 5.1 변경 파일 요약

| 유형 | 경로 |
|------|------|
| **삭제** | js/inspection-engine.js, js/rules-data.js, js/normalize.js |
| **수정** | adsafe.html, inspection-history.html, inspection-detail.html, main.js |
| **수정** | api/engine/rules_data.php, api/engine/inspection_engine.php, api/handlers/inspect.php |
| **신규** | docs/sql/seed_event_rules.sql |

### 5.2 검증 권장 절차

- **Phase A**  
  - adsafe.html, inspection-history.html, inspection-detail.html 로드 시 콘솔 에러 없음.  
  - 검수 3회, 이력 1회, 상세 1회 동작 확인. 리스크 이름이 API level3 또는 fallback 맵으로 표시되는지 확인.
- **Phase B**  
  - `docs/sql/seed_event_rules.sql` 실행 후, "60% 할인", "할인 이벤트 진행 중", "이벤트 진행 중" 등으로 검수.  
  - RISK_PRICE_EXCESSIVE, RISK_INDUCEMENT_CONDITION 적발·레벨 확인.  
  - 이력/상세 조회에서 inspection_runs.rule_set_version_id, inspection_findings.rule_id 저장 여부 확인.

### 5.3 기대 효과

- 검수 진입점이 PHP 단일 경로로 정리됨.
- 이벤트 관련 룰을 DB에서 관리 가능하여, 룰 변경 시 코드 배포 없이 조정 가능.
- 이력에 룰셋·룰 ID가 저장되어 감사·분석에 활용 가능.

---

## 6. 부록

### 6.1 참조 문서

- 플랜: `js_검수_엔진_제거_wp0_cd25d989.plan.md` (§0 진단, §7 실행 계획)
- ERD/스키마: `docs/sql/ERD_adsafe.md`, `docs/sql/adsafe_schema_mysql.sql`
- 검수 테스트 가이드: `docs/guides/test/검수_실사이트_테스트_가이드.md`

### 6.2 DB 이벤트 룰 적용 방법

```bash
# MySQL 클라이언트 예시 (환경에 맞게 DB명·사용자 변경)
mysql -u [user] -p [dbname] < docs/sql/seed_event_rules.sql
```

---

**보고서 끝**
