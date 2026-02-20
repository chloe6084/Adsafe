---
name: JS 검수 엔진 제거 WP0
overview: WP0(브라우저 JS 검수 엔진 제거) 및 이벤트 자동 체크 DB 이전. 룰 관리 방식은 rule_set_versions + rules 단일 테이블 유지. Build 시 §7 실행 계획 순서대로 동작.
todos:
  - id: A-1
    content: WP0 파일 삭제 (js/inspection-engine.js, js/rules-data.js, js/normalize.js)
    status: completed
  - id: A-2
    content: adsafe.html script 3줄 제거
    status: completed
  - id: A-3
    content: inspection-history.html script 1줄 제거
    status: completed
  - id: A-4
    content: inspection-detail.html script 1줄 제거
    status: completed
  - id: A-5
    content: main.js getRiskCodeName·표시 수정 (level3 우선, ADU_RULES 제거)
    status: completed
  - id: A-6
    content: WP0 검증 (페이지 로드·검수 3회·이력·상세)
    status: completed
  - id: B-1
    content: rules_data.php rule_id/rule_type/condition_json 조회·파싱
    status: completed
  - id: B-2
    content: 이벤트 룰 3건 DB INSERT (SQL 또는 시드 스크립트)
    status: completed
  - id: B-3
    content: inspection_engine.php 하드코딩 제거·numeric/combo 분기·rule_id
    status: completed
  - id: B-4
    content: inspect.php 저장 시 rule_set_version_id·rule_id 반영
    status: completed
  - id: B-5
    content: 이벤트 DB 이전 검증
    status: completed
isProject: false
---

# JS 검수 엔진 제거 (WP0) – Findings & Removal Plan

---

## 0. 검수 작동방식 진단 (멀티 롤 회의)

진단 목적: 검수가 **어디서·어떤 룰로·어떻게 저장되는지** 공유하고, JS 엔진 제거 후에도 동작이 유지되는지 확인하기 위함.

### 0-1. 현재 상태 진단 (공통)

**검수 호출 경로**

- **프론트**: [adsafe.html](c:\xampp\htdocs\Adsafe\adsafe.html) → [main.js](c:\xampp\htdocs\Adsafe\main.js) (검수하기 클릭) → `POST (ADSAFE_API_URL)/api/inspect` 호출.
- **기본값**: `ADSAFE_API_URL = '/AdSafe'` → **Apache 80 포트** → [api/index.php](c:\xampp\htdocs\Adsafe\api\index.php) → [api/routes.php](c:\xampp\htdocs\Adsafe\api\routes.php) `path === '/inspect'` → **PHP** [handle_inspect()](c:\xampp\htdocs\Adsafe\api\handlers\inspect.php).
- 문서상 **Apache(PHP)만으로 검수/이력** 동작하므로, 실제 검수 실행은 **PHP 경로**가 사용되는 구조.

**룰 소스 (검수 시 사용하는 룰)**


| 구분                                                                                           | 룰 소스                                                                                       | 비고                          |
| -------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ | --------------------------- |
| **PHP** [api/engine/rules_data.php](c:\xampp\htdocs\Adsafe\api\engine\rules_data.php)        | 1) DB **활성 룰셋**의 `rules` 2) 없으면 `rules_data.json`                                          | 이미 **DB 우선**                |
| **Node** [api/lib/inspection-engine.js](c:\xampp\htdocs\Adsafe\api\lib\inspection-engine.js) | [api/lib/rules-data.js](c:\xampp\htdocs\Adsafe\api\lib\rules-data.js) **ADU_RULES** (하드코딩) | DB 미사용, 현재 플로우에서 미호출        |
| **프론트** js/inspection-engine.js, js/rules-data.js                                            | 검수 API 호출만 함. **실제 검수 연산은 서버**에서 수행                                                        | 룰 소스와 무관(표시용 ADU_RULES만 사용) |


**이력 저장**

- [api/handlers/inspect.php](c:\xampp\htdocs\Adsafe\api\handlers\inspect.php): `inspection_runs` INSERT 시 `rule_set_version_id` → **NULL**. `inspection_findings` INSERT 시 `rule_id` → **NULL**.
- 따라서 "어떤 룰셋/룰로 적발했는지"는 **DB에 남지 않는 상태**.

---

### 0-2. 역할별 진단 및 할 일

**역할 1: 백엔드(검수 API)**

- **진단**: PHP 검수는 [rules_data.php](c:\xampp\htdocs\Adsafe\api\engine\rules_data.php)가 **활성 룰셋의 rules**를 DB에서 읽어 사용. Node 검수 라우트는 [api/routes/inspect.js](c:\xampp\htdocs\Adsafe\api\routes\inspect.js)에 있으나 현재 진입점은 PHP.
- **할 일**: (DB 룰 전환 플랜 시) 저장 시 `rule_set_version_id`/`rule_id` 기록. Node 경로 사용 시 DB에서 활성 룰 로드하거나 PHP만 사용하도록 정리.

**역할 2: 룰엔진/검수 로직**

- **진단**: PHP [inspection_engine.php](c:\xampp\htdocs\Adsafe\api\engine\inspection_engine.php)는 `adsafe_rules()`로 받은 룰(keywords/regex)로 매칭. 이벤트 자동 체크(할인 50%, 이벤트 기간 등)는 엔진 코드에 하드코딩.
- **할 일**: (선택) 이벤트 자동 체크를 DB 룰로 이전 또는 시스템 룰로 명시. finding에 `rule_id` 부여 시 엔진이 rule_id 반환하도록 수정.

**역할 3: DB/시드/운영**

- **진단**: 시드([api/scripts/seed.js](c:\xampp\htdocs\Adsafe\api\scripts\seed.js)) 후 `rule_set_versions`(v1.0.0), `rules`, `risk_taxonomy` 존재. PHP는 DB 룰로 검수 가능. `rules_data.json` fallback 있음.
- **할 일**: 활성 룰셋 보장 유지. (선택) "DB만 사용" 시 fallback 제거/경고. 비상 폴백 사용 시 "JSON으로 검수됨" 메시지 노출은 별도 작업.

**역할 4: 프론트엔드**

- **진단**: 검수하기는 **서버 API 결과만** 표시. [main.js](c:\xampp\htdocs\Adsafe\main.js)는 `renderInspectionResultWithData(data)`로 응답 렌더링. 리스크 이름은 `getRiskCodeName(finding.riskCode)` 사용 → 현재 `window.ADU_RULES` 또는 하드코딩 맵.
- **할 일**: JS 검수 엔진(js/inspection-engine.js, js/rules-data.js, js/normalize.js) 제거 시, **API가 내려주는 `finding.level3` 우선 사용**하고 `window.ADU_RULES` 의존 제거. 필수 변경은 [main.js](c:\xampp\htdocs\Adsafe\main.js) 및 script 태그 제거만 있으면 됨.

---

### 0-3. 진단 결론 (회의 요약)

- **검수 실행**: PHP가 유일 진입점. DB 활성 룰셋 사용, 없으면 JSON fallback.
- **브라우저 JS 엔진**: 검수 연산에 **미사용**. 표시용으로만 `ADU_RULES`/getRiskCodeName 사용 중.
- **제거 시 영향**: 스크립트 3개 삭제 + script 태그 제거 + main.js에서 level3 우선/ADU_RULES 제거 시, 검수 작동방식은 그대로이고 표시만 API 기준으로 통일됨.

---

### 0-4. 이벤트 자동 체크: 상태, 진단, 결론

**상태 (현재 구현)**

- **위치**: 동일 로직이 **3곳**에 하드코딩됨.
  - [api/engine/inspection_engine.php](c:\xampp\htdocs\Adsafe\api\engine\inspection_engine.php) 95~138행 (PHP, 실제 검수 경로)
  - [api/lib/inspection-engine.js](c:\xampp\htdocs\Adsafe\api\lib\inspection-engine.js) 56~89행 (Node)
  - [js/inspection-engine.js](c:\xampp\htdocs\Adsafe\js\inspection-engine.js) 56~92행 (브라우저, WP0에서 제거 예정)
- **내용**  
  - **RISK_PRICE_EXCESSIVE**  
    - (1) 정규식으로 `N% 할인/이벤트/오프` 매칭 → `N >= 50`이면 high, 설명/수정문 고정.  
    - (2) “할인|이벤트|특가|반값|무료”는 있는데 “N% 할인/이벤트/오프”가 없으면 “할인율 미기재” medium.
  - **RISK_INDUCEMENT_CONDITION**  
    - “이벤트|할인|특가|프로모션” 있고, (“선착순|한정|오늘만|당첨자|후기 조건” 있음 **또는** 기간 패턴 없음) → 이벤트 기간 미표기/조건부 혜택으로 적발, level은 기간 유무/조건 유무에 따라 high/medium/low.
- **특징**: 키워드·정규식·숫자 임계값(50%)·조건 조합(키워드 A 있음 + B 없음 등)이 한 덩어리로 코드에만 있음. DB `rules` 테이블의 기존 keyword/regex 단일 패턴과는 형태가 다름.

**진단 (DB 통합 시 고려사항)**

- **스키마**: 현재 `rules`는 `rule_type`(keyword, regex, numeric, combo), `pattern`, `condition_json` 등 지원. 이벤트 자동 체크는 “숫자 임계값 + 문맥 정규식” 또는 “여러 조건 조합”에 가깝다.
- **통합 방향 후보**  
  - (A) **combo / numeric_rule 확장**: `condition_json`에 “정규식으로 숫자 추출 → 50 이상이면 적발”, “키워드 그룹 A 존재 && 키워드 그룹 B 미존재” 등을 정의하고, 엔진이 DB 룰 로드 후 해당 rule_type만 별도 실행.  
  - (B) **시스템 룰 테이블**: `system_rules` 또는 `rule_templates` 같은 별도 테이블에 “이벤트 자동 체크”용 규칙만 두고, 엔진은 “DB rules + system_rules” 순서로 실행. 수정은 DB/관리 화면으로만 가능.
- **공통**: 이벤트 자동 체크에서 적발된 finding도 `rule_id`를 넣으려면, 해당 룰이 DB(또는 시스템 룰)에 한 건으로 존재해야 하고, 엔진이 매칭 시 그 rule_id를 반환하도록 수정해야 함.

**결론**

- 이벤트 자동 체크는 **DB로 통합하는 것이 적합**하다.  
  - 이유: 룰 변경 시 코드 배포 없이 관리 가능, 이력에서 “어떤 룰로 적발됐는지” 일원화, PHP/Node 등 다중 엔진에서 같은 정의 사용 가능.
- **권장**:  
  - 1. `rules`의 `rule_type`/`condition_json`으로 “numeric + 문맥”, “combo(조건 조합)” 규칙을 표현할 수 있도록 스키마/엔진을 설계하고,
  - 1. 기존 이벤트 자동 체크 3종(RISK_PRICE_EXCESSIVE 50% 이상, 할인율 미기재, RISK_INDUCEMENT_CONDITION)을 해당 형식의 DB 룰(또는 시스템 룰)로 이전한 뒤,
  - 1. PHP/Node 엔진에서는 “DB(또는 시스템) 룰 적용” 한 곳에서만 실행하도록 하드코딩 블록을 제거.
- **이번 WP0 범위**: 이벤트 자동 체크의 **DB 통합 작업은 포함하지 않음**. WP0에서는 브라우저 JS 엔진 제거만 수행. 이벤트 자동 체크 DB 통합은 **별도 작업**으로 진행하고, 완료 시 위 3곳 하드코딩을 단계적으로 제거하는 것으로 정리.

---

### 0-4-1. 룰 저장 구조 정책: 테이블 증식 방지 (멀티 롤 회의)

**아젠다**

- 앞으로 **법령/가이드 원문 → 구조화 → 룰 후보 → 검증 → 룰셋 배포(draft→active)** 파이프라인을 적용하면, “이벤트 자동 체크”처럼 **유형이 추가될 때마다** `system_rules`, `rule_templates` 등 **별도 테이블**을 두는 방식은 테이블 수가 계속 늘어날 우려가 있음.
- 따라서 **“단일 `rules` 테이블로 모든 룰 유형을 수용할지”, “테이블을 나누되 역할을 명확히 할지”**를 멀티 롤 회의로 정리함.

---

**역할 1: 기획/운영**

- **입장**: 파이프라인에서 나오는 결과는 결국 “검수 시 적용할 룰” 하나의 흐름으로 관리되고, 배포도 “룰셋 버전(draft→active)” 하나로 통제하는 게 운영상 단순함. 룰 유형(keyword, regex, numeric, combo, 그 외 조합형)이 늘어나도 **“룰 후보 → 검증 → 룰셋에 포함”**은 동일한 라이프사이클로 두는 게 좋음.
- **제안**: 유형별로 테이블을 나누지 말고, **“적용할 룰은 모두 `rules`(룰셋 버전 소속) 한 테이블에 두고, `rule_type` + `pattern` + `condition_json`으로 형태만 구분”**하는 쪽을 선호. 이렇게 하면 파이프라인 출력도 항상 `rules`에 INSERT/UPDATE만 하면 됨.

---

**역할 2: DB/스키마**

- **입장**: 테이블이 늘어나면 마이그레이션·조회·권한·백업이 모두 늘어남. `rules`는 이미 `rule_type` ENUM(keyword, regex, numeric, combo), `pattern` TEXT, `condition_json` JSON을 갖고 있으므로, **새 “유형”은 ENUM 값 추가 또는 condition_json 스키마 확장**으로 흡수 가능.
- **제안**: **단일 테이블 정책**을 권장.  
  - (1) 이벤트 자동 체크처럼 “숫자 임계값 + 문맥 정규식” → `rule_type = 'numeric'` 또는 `'combo'`, `condition_json`에 `{ "extract_regex": "...", "threshold": 50, "context_keywords": [...] }` 형태로 저장.  
  - (2) “키워드 A 있음 + B 없음” 조합 → `rule_type = 'combo'`, `condition_json`에 `{ "require": ["그룹A 키워드"], "forbid_if_absent": ["그룹B 키워드"] }` 등으로 표현.  
  - (3) 앞으로 법령 파이프라인에서 나온 새 패턴도 “rule_type 값 추가” 또는 “condition_json 스키마 한 종류 더”로 흡수. **테이블 추가는 하지 않음.**

---

**역할 3: 룰엔진/검수 로직**

- **입장**: 엔진은 “활성 룰셋의 `rules` N건을 읽어서, `rule_type`별로 분기해 실행”하면 됨. `system_rules` 등 별도 테이블이 있으면 “rules 로드 + system_rules 로드 + 실행 순서 정하기”가 늘어나고, 룰셋 버전(draft/active)과도 불일치할 수 있음.
- **제안**: **모든 적용 룰을 `rules` 한 테이블에서만 로드.** rule_type이 keyword/regex면 기존 로직, numeric/combo면 condition_json 파싱 후 별도 분기. “이벤트 자동 체크”도 “combo/numeric 타입의 rules 3건”으로 넣고, 엔진은 타입만 보고 같은 루프에서 처리. 이렇게 하면 **system_rules, rule_templates 테이블은 두지 않아도 됨.**

---

**역할 4: 백엔드/API**

- **입장**: 룰 목록/수정/배포 API는 “rule_set_versions + rules”만 다루면 됨. 별도 테이블이 생기면 “rules CRUD”, “system_rules CRUD”, “어떤 건 검수에 쓰이는지” 조합 로직이 복잡해짐.
- **제안**: **단일 `rules` 테이블 유지.** 관리 화면에서 “이벤트 자동 체크”용 룰도 동일하게 rule_set_version에 포함해 draft → active 배포. 검수 시에는 “활성 룰셋의 rules 전부”만 가져와 rule_type별 실행.

---

**회의 결론 (테이블 증식 방지)**

- **정책**: **별도 테이블(system_rules, rule_templates 등)을 추가하지 않고, 기존 `rules` 테이블 한 곳으로만 모든 룰 유형을 수용한다.**
- **방법**:  
  - (1) **rule_type**: 필요 시 ENUM에 값 추가(numeric, combo는 이미 있음). “이벤트 자동 체크” 스타일은 numeric 또는 combo + condition_json으로 표현.  
  - (2) **condition_json**: 유형별로 스키마를 정의해 두고(예: numeric_threshold, combo_conditions), 엔진은 rule_type + condition_json만 보고 실행. 새 패턴이 생기면 “condition_json 스키마 한 종류 추가” 또는 “기존 스키마 확장”으로 처리.  
  - (3) **파이프라인**: 법령→구조화→룰 후보→검증→배포의 출력은 항상 “rules 테이블에 넣을 레코드”로 통일. draft 룰셋에 넣었다가 검증 후 active로 전환하는 흐름은 그대로 유지.
- **효과**: 테이블 수는 고정되고, 룰 유형이 늘어나도 `rules`의 rule_type/condition_json만 확장하면 되어, 운영·엔진·API가 모두 단순하게 유지됨.

---

### 0-4-2. 이벤트 자동 체크 DB 이전 계획서 (기존 방식 활용)

**기존에 하려던 방식 요약**

- **룰 저장**: 별도 테이블 없이 **단일 `rules` 테이블**만 사용. `rule_type`(keyword, regex, **numeric**, **combo**) + `pattern` + **condition_json**으로 모든 유형 수용.
- **condition_json**: 유형별로 “구조(스키마)”만 정해 두고, numeric은 “숫자 추출 정규식 + 임계값”, combo는 “필요 키워드 그룹 + 미존재 시 조건” 등을 JSON으로 저장.
- **엔진**: 활성 룰셋의 `rules`를 전부 로드한 뒤 **rule_type별로 분기** — keyword/regex는 기존처럼 pattern 기반, numeric/combo는 condition_json 파싱 후 실행. 이벤트 자동 체크도 “numeric/combo 타입 rules N건”으로 두고 같은 루프에서 처리.
- **룰 관리**: 기존과 동일. 룰 추가/수정/배포는 rule_set_versions + rules만 다루고, draft → active 전환으로 배포. 법령→구조화→룰 후보→검증→배포 파이프라인 출력도 `rules`에 넣을 레코드로 통일.

위 방식을 그대로 활용해, **이벤트 자동 체크를 코드에서 빼고 DB 룰로 옮기는** 구체 계획은 아래와 같다.

---

**1단계: condition_json 구조 정의**

- **numeric_threshold** (할인율 50% 이상):  
`rule_type = 'numeric'`, `condition_json` 예시:

```json
  {
    "extract_regex": "(\\\\d+)\\\\s*%\\\\s*(할인|이벤트|오프)",
    "threshold": 50,
    "threshold_op": ">=",
    "match_group": 1,
    "matched_label": "50% 이상"
  }
  

```

- 엔진: 정규식으로 숫자 추출 → threshold 이상이면 해당 risk_code로 finding 추가, matched_text는 매칭된 문자열.
- **combo** (할인율 미기재):  
`rule_type = 'combo'`, `condition_json` 예시:

```json
  {
    "require_keywords": ["할인", "이벤트", "특가", "반값", "무료"],
    "require_keywords_match": "any",
    "forbid_regex": "\\\\d+\\\\s*%\\\\s*(할인|이벤트|오프)",
    "matched_label": "할인율 미기재"
  }
  

```

- 엔진: require_keywords 중 하나라도 있고, forbid_regex에 매칭이 없으면 적발. (기존 “이미 RISK_PRICE_EXCESSIVE 50% 적발이 있으면 제외”는 findings 상태로 판단.)
- **combo** (이벤트 기간/조건부):  
`rule_type = 'combo'`, `condition_json` 예시:

```json
  {
    "require_keywords": ["이벤트", "할인", "특가", "프로모션"],
    "require_keywords_match": "any",
    "optional_condition_keywords": ["선착순", "한정", "오늘만", "당첨자", "후기 조건"],
    "optional_period_regex": "(\\\\d{4}\\\\s*[.-/]\\\\s*\\\\d{1,2}|\\\\d{1,2}\\\\s*월|기간|종료|까지)",
    "matched_label": "이벤트 기간 미표기",
    "level_if_no_period": "high",
    "level_if_condition": "medium"
  }
  

```

- 엔진: 이벤트 키워드 있음 + (조건 키워드 있음 또는 기간 패턴 없음) → 적발, level은 기간/조건 유무에 따라 결정.

(실제 DB/엔진에서는 정규식 이스케이프는 환경에 맞게 적용.)

---

**2단계: DB 데이터 준비**

- **risk_taxonomy**: `RISK_PRICE_EXCESSIVE`, `RISK_INDUCEMENT_CONDITION` 존재 확인. 없으면 시드 또는 마이그레이션으로 추가.
- **활성 룰셋**: 현재 `status = 'active'`인 `rule_set_version_id` 확인.
- **rules INSERT**: 해당 룰셋에 아래 3건을 INSERT (rule_type, pattern, condition_json, risk_code, rule_name, severity_override, explanation_template, suggestion_template, is_active).
  - (1) **할인 50% 이상**: rule_type=numeric, risk_code=RISK_PRICE_EXCESSIVE, condition_json = 위 numeric_threshold.
  - (2) **할인율 미기재**: rule_type=combo, risk_code=RISK_PRICE_EXCESSIVE, condition_json = 위 combo(할인율 미기재).
  - (3) **이벤트 기간/조건부**: rule_type=combo, risk_code=RISK_INDUCEMENT_CONDITION, condition_json = 위 combo(이벤트 기간).

`pattern`은 “이벤트 자동 체크”용이라 비워 두거나, 요약용 문자열(예: `event_numeric_50`, `event_combo_discount_missing`, `event_combo_period`)만 넣어도 됨. 엔진은 condition_json 기준으로 동작.

---

**3단계: 엔진 수정 (PHP)**

- **rules_data.php**: 이미 활성 룰셋의 `rules`를 조회하며 `rule_type`, `pattern`, `condition_json`을 가져옴. **numeric/combo** 행도 그대로 반환하되, 엔진이 쓸 수 있게 배열에 `rule_id`, `rule_type`, `condition_json`(파싱된 연관 배열) 포함.
- **inspection_engine.php**:
  - (1) 기존 “이벤트 자동 체크” 하드코딩 블록(95~138행) **제거**.
  - (2) `adsafe_rules()`에서 받은 룰을 **rule_type별로 분기**:
    - keyword/regex: 기존 로직 유지 (pattern → keywords/regex 파싱 후 매칭).
    - **numeric**: condition_json의 extract_regex로 숫자 추출, threshold 비교 후 조건 만족 시 addFinding(risk_code, level, matched_text, …), **rule_id 전달** (finding에 rule_id 포함하도록 addFinding 시그니처 확장).
    - **combo**: condition_json의 require_keywords / forbid_regex / optional_period 등에 따라 조건 평가 후 적발 시 addFinding(…, rule_id).
  - (3) finding에 `rule_id`를 넣어 반환하면, handle_inspect에서 `inspection_findings` INSERT 시 `rule_id` 컬럼에 저장.

---

**4단계: 엔진 수정 (Node, 선택)**

- 현재 검수 진입은 PHP이므로 우선순위는 낮음. Node 검수 경로를 유지할 경우:
  - **api/lib/inspection-engine.js**: PHP와 동일하게 “이벤트 자동 체크” 하드코딩 제거, DB에서 로드한 rules 중 rule_type=numeric/combo를 condition_json 기반으로 실행하는 분기 추가. finding에 rule_id 포함.

---

**5단계: 저장·이력**

- **inspection_runs**: 검수 시 사용한 `rule_set_version_id`를 저장 (이미 스키마에 있음).
- **inspection_findings**: 매칭된 룰이 있으면 `rule_id` 저장. numeric/combo에서 적발된 건도 rule_id로 연결.

---

**6단계: 검증**

- 기존 이벤트 자동 체크 케이스로 회귀 확인:
  - “60% 할인” → RISK_PRICE_EXCESSIVE high, 50% 이상.
  - “할인 이벤트 진행 중” (숫자% 없음) → RISK_PRICE_EXCESSIVE medium, 할인율 미기재.
  - “이벤트 진행 중” (기간/조건 없음) 또는 “선착순 할인” → RISK_INDUCEMENT_CONDITION, level 및 matched_label 적절.
- 검수 3회 + 이력/상세 조회에서 rule_set_version_id, rule_id가 채워지는지 확인.

---

**요약 (룰 관리 방식)**

- **앞으로 룰 관리**: 기존과 동일하게 **rule_set_versions + rules**만 사용. 이벤트 관련 룰도 동일 테이블에 rule_type=numeric/combo, condition_json으로 저장. 배포는 draft → active, 파이프라인 출력도 rules 레코드로 통일.
- **이번 이전 작업**: 위 1~6단계 순서로 진행하면, 하드코딩된 이벤트 자동 체크가 DB 룰로만 동작하고, 테이블을 새로 추가하지 않으며, 기존에 하려던 “단일 rules + rule_type/condition_json 확장” 방식을 그대로 활용하게 됨.

---

**Q&A: condition_json 파싱 후 실행 — 설계 및 실행 위치**

- **질문**: “condition_json 파싱 후 실행”에 대한 설계가 어떻게 되어 있나? 어디서 실행되게 되어 있나?
- **답변** (플랜에 반영):

**1) 파싱 설계**

- **파싱** = DB의 `condition_json`(JSON 문자열)을 애플리케이션에서 **연관 배열(객체)** 로 복원하는 단계.
- **담당 위치**: **룰을 로드하는 쪽**에서 수행. PHP 경로에서는 [api/engine/rules_data.php](c:\xampp\htdocs\Adsafe\api\engine\rules_data.php).
- **동작**:
  - `rules` 조회 시 `r.condition_json`을 SELECT에 포함.
  - 반환하는 룰 배열을 만들 때, `rule_type`이 `numeric` 또는 `combo`인 행은 `condition_json` 문자열을 **json_decode**해 연관 배열로 넣어서 전달. (예: `'condition' => json_decode($row['condition_json'], true)`.)
  - keyword/regex 타입은 기존처럼 `pattern`만 파싱해 `keywords`, `regex` 키로 넘기면 되고, numeric/combo는 `rule_id`, `rule_type`, `condition`(파싱된 객체)을 넘김.
- **결과**: 엔진은 “파싱이 끝난 룰 배열”만 받는다. 엔진 쪽에서는 JSON 문자열을 다시 파싱하지 않고, 이미 구조화된 `condition`만 사용.

**2) 실행 설계**

- **실행** = 전처리된 문구(normalized text)에 대해, 룰의 조건(condition)을 **평가하고, 조건을 만족하면 finding을 추가하는** 단계.
- **담당 위치**: **검수 엔진 한 곳**. PHP 경로에서는 [api/engine/inspection_engine.php](c:\xampp\htdocs\Adsafe\api\engine\inspection_engine.php), Node 경로에서는 [api/lib/inspection-engine.js](c:\xampp\htdocs\Adsafe\api\lib\inspection-engine.js).
- **동작**:
  - `adsafe_rules()`(PHP) 또는 DB에서 로드한 룰 배열(Node)을 받은 뒤, **룰별로 `rule_type` 분기**:
    - **keyword / regex**: 기존처럼 `keywords`, `regex`로 매칭 후 `addFinding(…, rule_id)`.
    - **numeric**: `rule['condition']`의 `extract_regex`, `threshold`, `threshold_op` 등으로 정규식 매칭 → 숫자 추출 → 비교 → 조건 만족 시 `addFinding(…, rule_id)`.
    - **combo**: `rule['condition']`의 `require_keywords`, `forbid_regex`, `optional_period_regex` 등으로 문구를 평가 → 조건 만족 시 `addFinding(…, rule_id)`.
  - 파싱은 **rules_data.php(또는 Node 쪽 룰 로더)** 에서만 하고, **실행(매칭·finding 추가)** 은 **inspection_engine 쪽 한 곳**에서만 수행.

**3) 어디서 실행되게 되어 있나**

- **실행이 이루어지는 파일**:
  - **현재 검수 진입이 PHP인 경우**: [api/engine/inspection_engine.php](c:\xampp\htdocs\Adsafe\api\engine\inspection_engine.php) — `adsafe_inspect_run()` 내부에서 `adsafe_rules()`로 룰 배열을 받고, 그 룰을 순회하며 rule_type별로 분기 실행.
  - **Node 검수 경로를 쓸 경우**: [api/lib/inspection-engine.js](c:\xampp\htdocs\Adsafe\api\lib\inspection-engine.js) — `run(rawText)` 안에서 동일하게 “룰 배열 순회 + rule_type별 분기 실행”.
- **정리**: condition_json은 **rules_data.php(또는 Node 룰 로더)에서 한 번만 파싱**하고, **실행은 검수 엔진(inspection_engine.php / inspection-engine.js) 한 곳**에서만 이루어진다. “파싱 후 실행”이란, “DB에서 읽은 JSON을 룰 로더에서 파싱한 뒤, 그 결과를 엔진에 넘기고, 엔진이 그 구조를 보고 실행(조건 평가·addFinding)”하는 흐름이다.

---

## 0-5. 실무 검수 작동방식 설계·개발 (멀티 롤 회의, AdSafe MVP 기준)

실무에서 검수 기능을 **어떻게 설계하고 개발하는지** 역할별로 정리. 현재 AdSafe MVP 구조를 전제로 한다.

---

**역할 1: 기획/요구사항**

- **설계**: 검수 = “문구 입력 → 전처리(normalize) → 룰 매칭 → 적발 목록 + 요약 레벨 → (선택) DB 저장 → 화면 표시”. MVP에서는 “한 번에 한 문구 검수, 이력 조회”까지만.
- **개발 관점**: 요구사항이 바뀌어도 **룰만 바꾸면 되는지**(DB/관리 화면), **엔진 로직까지 바꿔야 하는지** 구분. MVP는 “룰은 DB(또는 JSON)로 관리, 엔진은 전처리+키워드/정규식+이벤트 체크”로 고정해 두고, 룰 추가/수정은 DB로만 하는 걸 목표로 두는 게 실무적.

---

**역할 2: 백엔드(검수 API)**

- **설계**
  - **진입점 단일화**: 검수 실행은 **한 경로**만 쓰는 게 유지보수에 유리. AdSafe MVP는 PHP `POST /api/inspect` 하나로 통일하고, Node 검수 라우트는 “사용 안 함”이면 제거하거나 “나중에 마이그레이션용”으로만 유지.
  - **입력/출력**: 입력 `{ text, project?, title?, user_id? }`, 출력 `{ summary, findings, normalizedText?, processingMs?, runId?, saveError? }`. findings 항목에 `riskCode`, `riskLevel`, `matchedText`, `explanation`, `suggestion`, `level1`~`level3` 포함해 프론트가 룰 데이터 없이도 표시 가능하게.
- **개발**
  - **핸들러**: 인증/크레딧 체크(필요 시) → 엔진 호출 → DB 저장(workspace_id, created_by, rule_set_version_id, findings별 rule_id) → 응답. 저장 시 “어떤 룰셋/룰로 적발했는지”까지 넣으면 나중에 이력 분석·감사에 유리.
  - **에러**: DB 저장 실패해도 검수 결과는 반환하고 `saveError`로 알림. 비상 시 JSON fallback 사용 시 응답에 `source: 'fallback'` 같은 플래그 넣으면 프론트에서 “JSON으로 검수됨” 메시지 가능.

---

**역할 3: 룰엔진/검수 로직**

- **설계**
  - **룰 소스 1원화**: “검수 시 사용하는 룰”은 **DB 활성 룰셋 한 곳**에서만 읽는다. 실무에서는 시드/마이그레이션으로 최소 1개 활성 룰셋을 보장하고, fallback(JSON)은 “DB 장애 시에만” 제한적으로 사용.
  - **룰 형식**: MVP는 keyword + regex만으로도 대부분 커버. `rules.pattern`에 “키워드 나열” 또는 “regex: …” 형태로 저장하고, 엔진에서 파싱해 동일한 매칭 로직 사용. 이벤트 자동 체크(할인 50%, 이벤트 기간 미표기 등)는 0-4에서 정리한 대로 **DB rule 또는 system_rule로 이전**하면 “룰 변경 = 코드 배포 없음”으로 맞출 수 있음.
- **개발**
  - **엔진 단계**: (1) 전처리(normalize) (2) DB 룰 로드 → keyword/regex 룰 적용 (3) 이벤트 자동 체크(현재는 하드코딩, 추후 DB화) (4) summary level 계산 (5) findings 반환. 각 finding에 `rule_id`를 붙이면 저장·이력에서 “어떤 룰”인지 추적 가능.
  - **테스트**: “최고”, “완치”, “50% 할인”, “이벤트 기간 미표기” 등 대표 문구로 단위 테스트 또는 수동 3회 검수로 회귀 확인. 룰 추가/수정 후엔 해당 케이스만 추가 검증.

---

**역할 4: DB/시드/운영**

- **설계**
  - **스키마**: `risk_taxonomy`(리스크 분류) → `rule_set_versions`(룰셋 버전) → `rules`(실제 룰). `inspection_runs`는 `rule_set_version_id`, `inspection_findings`는 `rule_id`로 연결해 “그때 그 룰로 적발”을 남김.
  - **활성 룰셋**: `rule_set_versions.status = 'active'`인 건이 항상 1개 이상 있도록 시드/관리 프로세스로 유지. 룰 변경 시 “새 버전 추가 → 테스트 → 활성화” 플로우를 두면 실무에서 안전.
- **개발**
  - **시드**: workspace, user, risk_taxonomy, rule_set_versions, rules를 한 번에 넣는 스크립트(예: `node scripts/seed.js`) 유지. 배포/환경 추가 시 시드 1회 실행으로 즉시 검수 가능 상태 만들기.
  - **Fallback**: `rules_data.json`은 “DB 연결 실패 시에만” 사용하고, 사용 시 로그 또는 응답 플래그로 알림. 운영 기본 경로는 “DB만”으로 정책 확정 후 fallback 제거/경고 처리.

---

**역할 5: 프론트엔드**

- **설계**
  - **검수 호출**: “검수하기” 클릭 시 `POST /api/inspect` 한 번만 호출. **브라우저에서 룰 로드·검수 연산 하지 않음.** 서버가 유일한 검수 실행 주체.
  - **표시**: 응답의 `summary`, `findings`만으로 화면 구성. 리스크 이름은 `finding.level3` 우선, 없으면 `riskCode` 또는 클라이언트 fallback 맵. 룰 데이터(ADU_RULES) 스크립트는 제거해도 됨(WP0).
- **개발**
  - **결과 영역**: 요약(레벨, 메시지, 건수), 문구 하이라이트, 적발 목록(매칭 표현, 설명, 수정 가이드). DB 저장 실패 시 `saveError`로 안내. (선택) “JSON으로 검수됨”은 응답 플래그 있으면 그때만 문구 표시.

---

**회의 결론 (실무 적용)**

- **AdSafe MVP 기준**: 검수 = PHP 1경로, 룰 = DB 활성 룰셋(비상 시 JSON), 저장 = runs + findings + (추후) rule_set_version_id/rule_id. 프론트는 호출·표시만.
- **개발 순서**: (1) 진입점 단일화(PHP만 사용 또는 Node 제거) (2) 저장 시 룰셋/룰 ID 기록 (3) 이벤트 자동 체크 DB화 (4) fallback 사용 시 “JSON 검수” 안내. WP0은 (1)과 무관하게 “브라우저 JS 엔진 제거 + 표시를 API 기준으로 통일”만 수행.

---

## 1. Findings (팩트)

### 1-1. JS 검수 엔진 관련 파일 존재 여부


| 경로                                                                                  | 용도                                         | 현재 플로우에서 사용 여부                                      |
| ----------------------------------------------------------------------------------- | ------------------------------------------ | --------------------------------------------------- |
| [js/inspection-engine.js](c:\xampp\htdocs\Adsafe\js\inspection-engine.js)           | 브라우저 검수 엔진 (`window.AdSafeInspection.run`) | **미사용** – 검수는 `POST /api/inspect`만 호출               |
| [js/rules-data.js](c:\xampp\htdocs\Adsafe\js\rules-data.js)                         | 브라우저용 룰 데이터 (`window.ADU_RULES`)           | **표시용만** – `getRiskCodeName(code)`에서 level3 이름 조회   |
| [js/normalize.js](c:\xampp\htdocs\Adsafe\js\normalize.js)                           | 브라우저 전처리 (`window.AdSafeNormalize.run`)    | **미사용** – `inspection-engine.js`에서만 호출, 엔진 제거 시 불필요 |
| [api/lib/inspection-engine.js](c:\xampp\htdocs\Adsafe\api\lib\inspection-engine.js) | Node 검수 엔진                                 | Node 라우트용 – **이번 WP0에서 제거 대상 아님**                   |
| [api/lib/rules-data.js](c:\xampp\htdocs\Adsafe\api\lib\rules-data.js)               | Node 룰 데이터                                 | 시드/Node용 – **이번 WP0에서 제거 대상 아님**                    |


### 1-2. 참조하는 HTML/JS 파일 목록


| 파일                                                                        | 참조 내용                                                                              |
| ------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| [adsafe.html](c:\xampp\htdocs\Adsafe\adsafe.html)                         | `<script src="js/normalize.js">`, `js/rules-data.js`, `js/inspection-engine.js` 로드 |
| [inspection-history.html](c:\xampp\htdocs\Adsafe\inspection-history.html) | `<script src="js/rules-data.js">` 로드                                               |
| [inspection-detail.html](c:\xampp\htdocs\Adsafe\inspection-detail.html)   | `<script src="js/rules-data.js">` 로드                                               |
| [main.js](c:\xampp\htdocs\Adsafe\main.js)                                 | `getRiskCodeName(code)` 내부에서 `window.ADU_RULES`로 level3 조회, 없으면 하드코딩 맵 사용          |
| [js/rule-set-versions.js](c:\xampp\htdocs\Adsafe\js\rule-set-versions.js) | 주석에만 "ADU_RULES.length" 언급, 코드 참조 없음                                               |
| [js/audit-logs.js](c:\xampp\htdocs\Adsafe\js\audit-logs.js)               | 목업 텍스트 "ADU_RULES 기준" – 동작 영향 없음                                                   |


### 1-3. 검수 플로우 (현재)

- [main.js](c:\xampp\htdocs\Adsafe\main.js): "검수하기" 클릭 시 `fetch(ADSAFE_API_URL + '/api/inspect', { method: 'POST', body: JSON.stringify({ text, ... }) })` 만 호출.
- 실제 검수 연산은 **서버(PHP 또는 Node)** 에서만 수행. 브라우저의 `AdSafeInspection.run`은 **호출처 없음**.

---

## 2. Removal Plan (제거 대상 / 참조 제거 위치)

### 2-1. 제거할 파일 (브라우저 전용, 이번 커밋에서 삭제)

- `js/inspection-engine.js`
- `js/rules-data.js`
- `js/normalize.js` (엔진이 유일한 사용처이므로 함께 제거)

### 2-2. 참조 제거 / 동작 유지 수정


| 파일                                                                        | 변경 내용                                                                                                                                        |
| ------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| [adsafe.html](c:\xampp\htdocs\Adsafe\adsafe.html)                         | `js/normalize.js`, `js/rules-data.js`, `js/inspection-engine.js` 세 줄 `<script>` 제거                                                           |
| [inspection-history.html](c:\xampp\htdocs\Adsafe\inspection-history.html) | `js/rules-data.js` 한 줄 `<script>` 제거                                                                                                         |
| [inspection-detail.html](c:\xampp\htdocs\Adsafe\inspection-detail.html)   | `js/rules-data.js` 한 줄 `<script>` 제거                                                                                                         |
| [main.js](c:\xampp\htdocs\Adsafe\main.js)                                 | `getRiskCodeName(code)`: API가 내려주는 `finding.level3` 우선 사용하도록 호출부/함수 시그니처 조정. `window.ADU_RULES` 분기 제거하고 기존 하드코딩 맵만 사용(또는 level3 있으면 그대로 표시). |


### 2-3. 유지 (이번 WP0에서 변경 없음)

- `api/lib/inspection-engine.js`, `api/lib/rules-data.js`, `api/routes/inspect.js` (Node 검수 경로)
- `api/engine/rules_data.php`의 `rules_data.json` fallback (비상 폴백 유지 정책)
- `js/rule-set-versions.js`, `js/audit-logs.js` (코드 참조 없음/목업 문구만)

---

## 3. Diff 목록 (파일별 변경 요약)


| 파일                        | 변경 유형 | 요약                                                              |
| ------------------------- | ----- | --------------------------------------------------------------- |
| `js/inspection-engine.js` | 삭제    | 파일 전체 제거                                                        |
| `js/rules-data.js`        | 삭제    | 파일 전체 제거                                                        |
| `js/normalize.js`         | 삭제    | 파일 전체 제거                                                        |
| `adsafe.html`             | 수정    | script 3개 제거 (normalize, rules-data, inspection-engine)         |
| `inspection-history.html` | 수정    | script 1개 제거 (rules-data)                                       |
| `inspection-detail.html`  | 수정    | script 1개 제거 (rules-data)                                       |
| `main.js`                 | 수정    | `renderInspectionResultWithData`에서 finding 표시 시 `finding.level3 |


---

## 4. Verification (재현 절차 + 기대 결과)

### 4-1. 재현 절차

1. **페이지 로드**
  - `http://localhost/AdSafe/adsafe.html` 열기 → F12 콘솔에 스크립트 404/에러 없음.
  - `http://localhost/AdSafe/inspection-history.html` 열기 → 동일 확인.
  - `http://localhost/AdSafe/inspection-detail.html?id=1` (유효한 id) 열기 → 동일 확인.
2. **검수 실행 3회**
  - adsafe.html에서 문구 입력 후 "검수하기" 클릭 → 결과 영역에 요약/적발 목록/하이라이트 정상 표시.
  - 다른 문구로 2회 더 실행 → 동일하게 정상.
3. **이력/상세 조회**
  - inspection-history.html에서 목록 로드 → 테이블 정상 표시.
  - 항목 클릭해 inspection-detail.html 상세 조회 1회 → 요약/적발 목록 정상 표시.

### 4-2. 기대 결과

- 모든 페이지에서 콘솔 에러 없음 (특히 `inspection-engine.js`, `rules-data.js`, `normalize.js` 404 없음).
- 검수 결과/이력/상세에서 리스크 항목 이름이 **API의 level3 또는 main.js fallback 맵**으로 정상 표시.
- 검수 3회 및 이력 1회, 상세 1회 모두 기존과 동일하게 동작.

### 4-3. 비상 폴백 (JSON) 정책

- `rules_data.json` fallback은 유지. PHP에서 fallback 사용 시 "JSON으로 검수됨" 메시지 노출은 **별도 작업**으로 진행 (이번 WP0 커밋 범위 외).

---

## 5. 커밋 및 작업 순서

1. **제거 커밋 1개로 분리**: 위 Diff 목록대로 파일 삭제 3개 + 수정 4개를 한 커밋으로 적용.
2. 적용 후 위 Verification 4-1 절차 실행하고, 4-2 기대 결과 충족 여부를 리포트로 정리.

---

## 6. 멀티 롤 회의록 (맥락·의도·결정)

**회의 목적**: 현재까지 플랜의 맥락·의도를 공유하고, “룰 관리 방식” 및 “Build 시 실행할 작업”을 확정하기 위함.

**맥락 요약**

- 검수 실행은 PHP 단일 경로. 룰은 DB 활성 룰셋에서 로드하며, 브라우저 JS 검수 엔진은 미사용이므로 제거 대상.
- 이벤트 자동 체크(할인 50%, 할인율 미기재, 이벤트 기간/조건부)는 코드에 하드코딩되어 있으므로, 단일 `rules` 테이블 + `rule_type`/`condition_json` 확장으로 DB 이전하기로 함. 별도 테이블(system_rules 등)은 두지 않음.

**역할별 의견 및 결정**


| 역할        | 맥락·의도                                                                                   | 결정                                                                                       |
| --------- | --------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| **기획/운영** | 룰은 rule_set_versions + rules만 사용하고, 배포는 draft→active로 통일. 파이프라인 출력도 rules 레코드로만 관리.     | **합의**: 앞으로 룰 관리 방식 유지. 이벤트 룰도 동일 테이블에 numeric/combo + condition_json으로 저장.              |
| **백엔드**   | 검수 API는 PHP 유지. 저장 시 rule_set_version_id, findings별 rule_id 기록해 이력 추적 가능하게 함.           | **합의**: inspect 핸들러에서 runs/findings 저장 시 룰셋·룰 ID 반영.                                     |
| **룰엔진**   | rules_data.php에서 condition_json 파싱 후 엔진에 전달. 실행은 inspection_engine.php에서 rule_type별 분기. | **합의**: 파싱은 rules_data.php, 실행은 inspection_engine.php. 하드코딩 블록 제거 후 numeric/combo 분기 추가. |
| **DB/운영** | 테이블 추가 없이 rules만 사용. condition_json 구조만 정의해 두고 확장.                                      | **합의**: 이벤트 룰 3건을 활성 룰셋 rules에 INSERT. 시드 또는 마이그레이션 SQL로 반영.                             |
| **프론트**   | 검수 결과는 API 응답만으로 표시. 리스크 이름은 finding.level3 우선, fallback 맵 보조.                          | **합의**: WP0에서 js 엔진·rules-data 스크립트 제거, main.js에서 level3 우선 사용.                          |


**회의 결론**

- **룰 관리 방식**: 기존과 동일하게 **rule_set_versions + rules**만 사용. 이벤트 관련 룰도 동일 테이블에 rule_type=numeric/combo, condition_json으로 저장. 배포는 draft→active, 파이프라인 출력도 rules 레코드로 통일. **이 방식으로 진행한다.**
- **실행 순서**: (1) WP0 — 브라우저 JS 검수 엔진 제거 및 표시 API 기준 통일 (2) 이벤트 자동 체크 DB 이전 — rules_data 파싱 확장, 이벤트 룰 3건 INSERT, inspection_engine 하드코딩 제거·numeric/combo 실행, 저장 시 rule_set_version_id/rule_id 반영.
- **Build 시 동작**: 아래 §7 실행 계획의 단계를 순서대로 수행하면 전체 작업이 실행되도록 한다.

---

## 7. 실행 계획 (Build 클릭 시 동작)

Build 클릭 시 아래 단계를 **순서대로** 실행한다. 각 단계는 완료 후 다음 단계로 진행한다.

---

### Phase A: WP0 — 브라우저 JS 검수 엔진 제거


| 단계  | 작업                    | 파일/위치                                                                     | 상세                                                                                                                                                                                              |
| --- | --------------------- | ------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| A-1 | 파일 삭제                 | `js/inspection-engine.js`, `js/rules-data.js`, `js/normalize.js`          | 3개 파일 삭제.                                                                                                                                                                                       |
| A-2 | script 태그 제거          | [adsafe.html](c:\xampp\htdocs\Adsafe\adsafe.html)                         | `js/normalize.js`, `js/rules-data.js`, `js/inspection-engine.js` 로드하는 `<script>` 3줄 제거.                                                                                                         |
| A-3 | script 태그 제거          | [inspection-history.html](c:\xampp\htdocs\Adsafe\inspection-history.html) | `js/rules-data.js` 로드하는 `<script>` 1줄 제거.                                                                                                                                                       |
| A-4 | script 태그 제거          | [inspection-detail.html](c:\xampp\htdocs\Adsafe\inspection-detail.html)   | `js/rules-data.js` 로드하는 `<script>` 1줄 제거.                                                                                                                                                       |
| A-5 | getRiskCodeName·표시 수정 | [main.js](c:\xampp\htdocs\Adsafe\main.js)                                 | (1) `renderInspectionResultWithData` 내 finding 표시 시 **finding.level3**가 있으면 그대로 사용, 없으면 `getRiskCodeName(finding.riskCode)`. (2) `getRiskCodeName`에서 **window.ADU_RULES 분기 제거**, 기존 하드코딩 맵만 사용. |
| A-6 | 검증                    | —                                                                         | adsafe.html, inspection-history.html, inspection-detail.html 로드 시 콘솔 에러 없음. 검수 3회·이력 1회·상세 1회 동작 확인.                                                                                            |


---

### Phase B: 이벤트 자동 체크 DB 이전


| 단계  | 작업                           | 파일/위치                                                                                       | 상세                                                                                                                                                                                                                                                                                                                                                                                             |
| --- | ---------------------------- | ------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| B-1 | 룰 로드·파싱 확장                   | [api/engine/rules_data.php](c:\xampp\htdocs\Adsafe\api\engine\rules_data.php)               | SELECT에 `r.rule_id`, `r.rule_type`, `r.condition_json` 추가. 반환 배열 구성 시: keyword/regex는 기존처럼 pattern→keywords,regex; **numeric/combo**는 condition_json을 json_decode해 `'condition'` 키로 넣고, `rule_id`, `rule_type` 포함.                                                                                                                                                                             |
| B-2 | 이벤트 룰 3건 INSERT              | DB 또는 시드/마이그레이션                                                                             | 활성 룰셋(rule_set_version_id)에 대해: (1) 할인 50% 이상 — rule_type=numeric, risk_code=RISK_PRICE_EXCESSIVE, condition_json(numeric_threshold). (2) 할인율 미기재 — rule_type=combo, risk_code=RISK_PRICE_EXCESSIVE, condition_json(combo). (3) 이벤트 기간/조건부 — rule_type=combo, risk_code=RISK_INDUCEMENT_CONDITION, condition_json(combo). 0-4-2의 JSON 예시 참고. SQL 파일 또는 api/scripts/seed_event_rules.js 등으로 실행. |
| B-3 | 엔진: 하드코딩 제거·numeric/combo 실행 | [api/engine/inspection_engine.php](c:\xampp\htdocs\Adsafe\api\engine\inspection_engine.php) | (1) 이벤트 자동 체크 하드코딩 블록(95~138행 부근) **삭제**. (2) `adsafe_rules()` 결과를 rule_type별 분기: keyword/regex는 기존 로직 유지, **numeric**은 condition의 extract_regex·threshold로 숫자 추출·비교 후 addFinding(…, rule_id), **combo**는 condition의 require_keywords/forbid_regex 등으로 평가 후 addFinding(…, rule_id). (3) addFinding 시그니처에 rule_id 인자 추가, finding 배열에 rule_id 포함.                                                |
| B-4 | 저장 시 룰셋·룰 ID 반영              | [api/handlers/inspect.php](c:\xampp\htdocs\Adsafe\api\handlers\inspect.php)                 | inspection_runs INSERT 시 사용한 **rule_set_version_id** 설정. inspection_findings INSERT 시 각 finding의 **rule_id** 설정. (엔진이 반환한 rule_set_version_id는 rules_data에서 조회한 활성 룰셋 ID 사용.)                                                                                                                                                                                                                  |
| B-5 | 검증                           | —                                                                                           | "60% 할인", "할인 이벤트 진행 중", "이벤트 진행 중" 등으로 검수 3회. RISK_PRICE_EXCESSIVE, RISK_INDUCEMENT_CONDITION 적발·level 확인. 이력/상세에서 rule_set_version_id, rule_id 저장 여부 확인.                                                                                                                                                                                                                                     |


---

### 실행 순서 요약

1. **Phase A** 완료 후 **Phase B** 진행. Phase A 내에서는 A-1 → A-2 → A-3 → A-4 → A-5 → A-6 순서.
2. Phase B 내에서는 B-1 → B-2 → B-3 → B-4 → B-5 순서. (B-2는 B-1과 독립적이지만, B-3에서 numeric/combo 룰을 쓰므로 B-2 완료 후 B-3 실행.)
3. **Build** 클릭 시: Phase A부터 순차 실행한 뒤, Phase B를 순차 실행하면 전체 플랜이 반영된다.

---

## 8. 노션 문서 대비 실행 계획 검토

**참조**: 노션 내보내기 `클로이_진행중` (Problem Definition, PRD, FR/NFR, CRUD, ERD, MVP, Tech_Spec, Adsafe 리스크 검수 데이터 운영 원칙, 0213 업데이트 등)

### 8-1. 일치·정합성


| 노션 문서 내용                                                                                                                | 실행 계획과의 관계                                                                                          |
| ----------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| **검수 흐름** (3.2.1 광고 문구 검수): 검수하기 클릭 → 로딩 → 룰 엔진 분석 → 결과 표시 → 검수 이력 저장                                                   | 현재 구현은 **API 호출**로 룰 엔진이 서버에서 실행됨. Phase A에서 브라우저 엔진 제거해도 동일 시나리오 유지. **문제 없음.**                    |
| **ERD·검수 저장**: inspection_runs, inspection_findings, rule_set_version_id, rule_id 필드 의미                                 | Phase B-4에서 runs에 rule_set_version_id, findings에 rule_id 저장하도록 반영. **일치.**                          |
| **이벤트 자동 체크** (Adsafe 리스크 검수 데이터 운영 원칙 §9): RISK_PRICE_EXCESSIVE 50% 초과/할인율 미기재, RISK_INDUCEMENT_CONDITION 이벤트 기간 미기재 등 | Phase B는 해당 로직을 DB 룰(numeric/combo + condition_json)로 이전. 판정 단위·risk_code 모두 노션 테이블과 동일. **문제 없음.** |
| **전처리 + 패턴 매칭** (운영 원칙): 정확도는 전처리·패턴 매칭 단계에서 결정                                                                         | WP0은 프론트 엔진만 제거하고, 서버(PHP) 전처리·패턴 매칭·이벤트 체크는 유지·DB 이전. **문제 없음.**                                   |
| **API 명세**: POST /api/inspect, GET /api/inspection-history(/:id)                                                        | 실행 계획은 API 시그니처 변경 없음. **문제 없음.**                                                                   |
| **0213 업데이트**: 검수 일 5회(Free), 크레딧, AI 생성 등                                                                              | 실행 계획과 무관. **충돌 없음.**                                                                               |


### 8-2. 노션 문서와의 차이·추가 반영 권장


| 항목                      | 노션 현재                                                    | 실행 계획 반영 후                                                           | 권장                                                |
| ----------------------- | -------------------------------------------------------- | -------------------------------------------------------------------- | ------------------------------------------------- |
| **MVP 2.2 AdSafe Mode** | "검수하기 = main.js, **inspection-engine.js**, normalize.js" | 실제로는 검수 시 **POST /api/inspect**만 호출, 엔진은 서버. Phase A 완료 시 브라우저엔진 제거. | 노션을 "검수 시 **API 호출**, 브라우저 검수 엔진 미사용"으로 수정 권장.    |
| **FR-102 / 룰 CRUD**     | rule_type "keyword                                       | regex" 만 명시                                                          | Phase B에서 **numeric, combo** 사용.                  |
| **Rules 데이터 구조 (3.4)**  | rule_type keyword                                        | regex                                                                | DB 스키마는 이미 numeric, combo ENUM 포함. 실행 계획은 그대로 활용. |


### 8-3. 결론

- **실행 계획(§7 Phase A·B)은 노션 문서와 충돌하지 않으며, 그대로 진행해도 됨.**
- Phase A: 브라우저 JS 검수 엔진 제거 → 노션의 "검수하기 = API 호출 → 결과·이력" 흐름과 부합.
- Phase B: 이벤트 자동 체크 DB 이전 → 노션의 이벤트 자동 체크 로직 테이블·ERD와 방향 일치.
- 실행 완료 후, 위 8-2의 노션 문서 업데이트를 해 두면 제품 명세와 구현이 맞게 유지됨.

