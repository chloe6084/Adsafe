# 멀티 롤 회의 — Node·Express 잔존 정책 (아젠다)

**일자**: 2026-02-13  
**안건**: 운영 스택 정책 확정 — **Node·Express 미사용**, **PHP + 필요 시 Python + OpenAPI** 로 통일. 초기 Node·Express 잔존물 제거·이전 방안 및 구현 로드맵.

**회의 후**: 결론에 따라 **Cursor 플랜 모드**로 구현 진행 예정.

---

## 1. 기술 스택 정책 (결정 방향)

### 1.1 운영·개발에서 사용하는 스택

| 구분 | 사용 | 비고 |
|------|------|------|
| **백엔드·API** | **PHP** | API 진입점·검수·이력·DB 연동 |
| **추가 백엔드** | **필요 시 Python** | 특수 로직·배치·분석 등 |
| **API 명세** | **OpenAPI** | API 설계·문서·클라이언트 생성 등 |
| **프론트·문서** | **HTML, JS, MD, CSS** | 기존처럼 유지 (프론트·문서용) |
| **미사용** | **Node, Express** | 초기 구성에서만 사용했으며, **운영 시 불필요** → 제거·이전 대상 |

### 1.2 정책 요약

- **운영 시 Node·Express 불필요.** PHP, 필요 시 Python, OpenAPI만 사용.
- **HTML, JS, MD, CSS** 는 기본적으로 그대로 가져감 (프론트·정적·문서).
- **초기에 쓰던 Node·Express** 는 사용하지 않음. 잔존 코드·스크립트는 **제거** 또는 **PHP(및 필요 시 Python)·SQL·OpenAPI** 로 이전.

---

## 1.3 Node·Express 제거 시 사이트 UI/UX·CSS(기능·형태) 영향

**결론: 영향 없음.** PHP·MySQL로 교체·제거해도 **화면·스타일** 은 그대로 유지됩니다.

| 구분 | 내용 |
|------|------|
| **API 호출** | 프론트(HTML/JS)는 `window.ADSAFE_API_URL` + `/api/...` 로 요청. XAMPP/Apache 사용 시 이 요청은 **PHP** (`api/index.php` → `routes.php` → handlers)가 처리함. **Node 서버는 사용하지 않음.** |
| **응답 형식** | 검수·이력 등 API 응답(JSON)은 **PHP**가 이미 동일한 형식으로 제공 중. Node 제거해도 응답 구조 변경 없음. |
| **UI·렌더링** | 화면은 **정적 HTML + Bootstrap(CDN) + styles.css + main.js** 등으로 구성. Node는 **서버 사이드 렌더링·CSS 생성에 관여하지 않음.** |
| **형태(레이아웃·스타일)** | CSS는 루트 `styles.css`, Bootstrap CDN, 각 HTML 내 스타일. Node·Express 제거와 무관. |

**유일한 수정점(문구만)**  
- 프론트 **에러/안내 메시지** 안에 “시드 실행”을 안내하는 문구가 있음. 현재 **Node 시드**를 가리키는 문자열만 PHP 시드로 바꾸면 됨.  
  - **main.js** (약 79행, 174행): `"node scripts/seed.js"` → 시드 PHP 이전 후 `"php api/scripts/seed.php"` 등으로 **문구만 변경**.  
  - **adusafe-start.html** (약 65행): `"시드(npm run seed)"` → `"시드(php api/scripts/seed.php)"` 등으로 **문구만 변경**.  
- 위 변경은 **기능·형태가 아니라 안내 문구**만 바꾸는 것이므로, UI/UX·CSS에는 영향 없음.

---

## 1.4 Node·Express 제거 시 영향이 가는 것 (수정·삭제·대체 대상)

아래는 **실제로 변경·작업이 필요한 항목**만 정리한 목록입니다.

### (1) 삭제·대체되는 파일·폴더

| 구분 | 경로 | 조치 |
|------|------|------|
| API 서버 | `api/server.js` | 삭제 |
| 라우트 | `api/routes/inspect.js`, `api/routes/inspection-history.js` | 삭제 |
| Node 엔진/룰 | `api/lib/inspection-engine.js`, `api/lib/normalize.js` | 삭제 (PHP engine/ 사용) |
| Node 룰·DB | `api/lib/rules-data.js`, `api/config/db.js` | 시드 PHP 이전 후 삭제 |
| 시드·도구 | `api/scripts/seed.js`, `check-db.js`, `test-inspect-save.js` | PHP(또는 SQL)로 **이전 후** 삭제 |
| 패키지 | `api/package.json`, `api/package-lock.json`, `api/node_modules/` | Node 제거 시 삭제 |

### (2) 수정이 필요한 코드·문서 (문구·경로·절차)

| 구분 | 파일 | 내용 | 변경 방향 |
|------|------|------|-----------|
| **프론트 안내 문구** | `main.js` (약 79행, 174행) | `"node scripts/seed.js"` | → `"php api/scripts/seed.php"` 등 |
| **프론트 안내 문구** | `adusafe-start.html` (약 65행) | `"시드(npm run seed)"` | → `"시드(php api/scripts/seed.php)"` 등 |
| **API 에러 응답** | `api/handlers/users.php` (약 135행) | `"npm run seed"를 실행해 주세요` | → `"php api/scripts/seed.php"를 실행해 주세요"` 등 |
| **실행 가이드** | `api/실행_순서_가이드.md` | `npm run check-db`, `npm run seed`, `node scripts/test-inspect-save.js`, `npm start` 등 | → PHP 시드·check-db·테스트 절차로 전면 수정 |
| **API README** | `api/README.md` | `npm run seed` | → PHP 시드 실행 방법으로 수정 |
| **검수 테스트 가이드** | `docs/guides/test/검수_실사이트_테스트_가이드.md` | `npm run seed` | → PHP(또는 SQL) 시드 실행 방법으로 수정 |

### (3) 배포·운영 절차에 영향

| 항목 | 현재 | Node 제거 후 |
|------|------|--------------|
| **초기 DB 세팅** | `cd api` → `npm install`(선택) → `npm run seed` | `php api/scripts/seed.php` 또는 SQL 실행 |
| **DB 연결 진단** | `npm run check-db` | `php api/scripts/check-db.php` 또는 수동(api/health + DB 클라이언트) |
| **검수 저장 테스트** | `node scripts/test-inspect-save.js` | PHP CLI 또는 수동 검증 |
| **필요 런타임** | Apache + PHP + MySQL + (선택) Node | Apache + PHP + MySQL 만 |
| **배포 체크리스트** | Node·npm·시드 명령 포함 가능 | Node·npm 제거, PHP 시드만 명시 |

### (4) 참고용 문서·플랜 (선택 반영)

- `docs/meetings/agenda/프로젝트_폴더구조_정리_아젠다.md` — Part II Node 관련 안건은 전용 회의로 이전됨.
- `.cursor/plans/js_검수_엔진_제거_wp0_cd25d989.plan.md` — 과거 플랜에서 Node 시드·엔진 언급; Node 제거 정책 반영 시 “시드 = PHP/SQL”로 문구 정리 가능.

### (5) 영향 없음 (변경 불필요)

- **UI/UX·CSS·화면 형태**: 변경 없음 (§1.3).
- **API 엔드포인트·URL**: PHP가 동일 경로 제공, 변경 없음.
- **API 응답 JSON 구조**: 검수·이력 등 PHP와 동일, 변경 없음.
- **HTML·Bootstrap·styles.css·프론트 JS 로직**: Node와 무관, 변경 없음.

---

## 2. 현황 — Node·Express 잔존 목록

### 2.1 런타임 API (이미 PHP로 대체됨, 삭제 대상)

| 경로 | 용도 | 조치 |
|------|------|------|
| `api/server.js` | Express 서버 | 삭제 |
| `api/routes/inspect.js` | Node 검수 라우트 | 삭제 |
| `api/routes/inspection-history.js` | Node 이력 라우트 | 삭제 |
| `api/lib/inspection-engine.js` | Node 검수 엔진 | 삭제 (PHP engine/ 사용) |
| `api/lib/rules-data.js` | Node 룰 데이터 | 시드 이전 후 삭제 |
| `api/lib/normalize.js` | Node 전처리 | 삭제 (PHP normalize.php 사용) |
| `api/config/db.js` | Node MySQL 연결 | 시드 이전 후 삭제 |

### 2.2 시드·도구 (PHP·SQL로 이전 대상)

| 경로 | 용도 | 이전 방향 |
|------|------|-----------|
| `api/scripts/seed.js` | workspaces, users, risk_taxonomy, quizzes 등 초기 데이터 | **PHP CLI 스크립트** 또는 **SQL 시드 확장** |
| `api/scripts/check-db.js` | DB 연결·.env·SSL 진단 | **PHP 스크립트** 또는 배포 체크리스트(수동) |
| `api/scripts/test-inspect-save.js` | 검수 저장 연동 테스트 | **PHP CLI** 또는 수동 검증 절차 |

### 2.3 패키지·의존성

| 경로 | 조치 |
|------|------|
| `api/package.json`, `api/package-lock.json` | Node 제거 시 **삭제** (또는 프로젝트 루트에 프론트 빌드용만 유지 시 예외 정책) |
| `api/node_modules/` | `npm install` 불필요화 후 **삭제** |

---

## 3. 역할별 진단·입장 (정책 반영)

### 역할 1: 기획/제품

- **진단**: “운영 시 Node·Express 불필요, PHP + 필요 시 Python + OpenAPI” 정책이면 **잔존 Node·Express 제거**가 일관됨.
- **입장**:  
  - **제거 범위 확정**: server.js·routes·lib(Node)·config/db.js·scripts(시드·check-db·test) → PHP·SQL·문서로 이전 후 삭제.  
  - **문서**: README·실행 가이드에서 “Node·Express 미사용, PHP(및 필요 시 Python)·OpenAPI만 사용” 명시.  
- **제안**: 회의에서 **제거·이전 단계(Phase)** 와 **Cursor 플랜 모드 구현 순서** 합의.

---

### 역할 2: 백엔드/API

- **진단**: API는 이미 PHP. Node 쪽은 **시드가 ADU_RULES·ADU_QUESTIONS** 를 참조하므로, 이전 시 **PHP에서 동일 데이터 소스** 사용 또는 **SQL·JSON 고정 데이터**로 정리 필요.
- **입장**:  
  - **즉시 삭제 가능**: server.js, routes/*.js, lib/inspection-engine.js, lib/normalize.js (PHP에 동일 기능 있음).  
  - **이전 후 삭제**: seed.js → PHP CLI `api/scripts/seed.php` (또는 docs/sql 시드 확장), check-db.js → `api/scripts/check-db.php` 또는 가이드만, test-inspect-save → PHP 또는 수동 절차.  
  - **OpenAPI**: 기존 PHP API에 대해 **OpenAPI 3.x 스펙** 작성(선택, 단계별).  
- **제안**:  
  - Phase 1: Node API·엔진·라우트·불필요 lib 삭제.  
  - Phase 2: 시드·check-db·test를 PHP(또는 SQL)로 이전 후 Node 스크립트·package.json·node_modules 제거.

---

### 역할 3: DB/운영

- **진단**: 시드·진단이 PHP 또는 SQL만으로 동작하면 **배포 시 Node 설치 불필요**.
- **입장**:  
  - 시드: **PHP CLI** (`php api/scripts/seed.php`) 또는 **순수 SQL** (docs/sql/ 에 INSERT 묶음) 중 선택. ADU_RULES·QUESTIONS 반영은 PHP에서 DB/JSON 읽어 INSERT 하거나, SQL에 고정값으로 넣기.  
  - check-db: PHP로 DB 연결 테스트 스크립트 제공 또는 “수동으로 api/health 호출·DB 클라이언트 접속” 가이드.  
- **제안**: 시드 데이터 구조(workspaces, users, risk_taxonomy, quizzes 등)를 문서화하고, PHP 시드 스크립트 입력(env·설정 경로) 정의.

---

### 역할 4: 배포/인프라

- **진단**: “운영 시 Node·Express 불필요” 달성을 위해 **Node 제거**가 필수.
- **입장**:  
  - 배포 체크리스트에서 `node`, `npm`, `npm run seed` 제거.  
  - **PHP + Apache(+ MySQL)** 만으로 실행·시드·헬스 확인 가능하도록 문서화.  
- **제안**: 실행 가이드·배포 문서를 “PHP만 사용” 기준으로 수정.

---

## 4. 회의 아젠다 (결정·구현 순서)

| # | 안건 | 옵션 / 내용 | 비고 |
|---|------|-------------|------|
| 1 | **정책 확정** | 운영: PHP + 필요 시 Python + OpenAPI. Node·Express 미사용. HTML·JS·MD·CSS 유지. | 본 문서 §1 방향 |
| 2 | **Node·Express 제거 범위** | API 서버·라우트·Node 엔진·lib·db.js 삭제 + 시드·도구 PHP/SQL 이전 후 스크립트·package 제거 | Phase 구분 |
| 3 | **시드 이전 방식** | PHP CLI 스크립트 / 순수 SQL 확장 / 혼합(PHP가 SQL 또는 JSON 읽어 INSERT) | ADU_RULES·QUESTIONS 반영 |
| 4 | **check-db·test 이전** | PHP 스크립트 / 수동·가이드만 / 제거 | 배포·개발 체크리스트 |
| 5 | **OpenAPI 도입 시점** | 즉시 / Phase 2 이후 / 별도 이슈 | API 문서·클라이언트 |
| 6 | **구현 순서(플랜)** | Phase 1 삭제 → Phase 2 시드·도구 이전 → 문서 정리 | Cursor 플랜 모드용 |

---

## 5. 확정 결론 (회의 반영 후 — 구현 기준)

아래는 **사용 방향에 따른 권장 결론**이며, 회의에서 확정 시 **Cursor 플랜 모드 구현**의 기준으로 사용.

### 5.1 기술 스택

- **운영·API**: **PHP**. 필요 시 **Python**. **OpenAPI** 로 API 명세·문서.
- **프론트·문서**: **HTML, JS, MD, CSS** 유지.
- **Node·Express**: **사용하지 않음.** 잔존 코드는 제거·이전.

### 5.2 Phase 1 — Node API·엔진·라우트 제거

- **삭제**:  
  - `api/server.js`  
  - `api/routes/inspect.js`, `api/routes/inspection-history.js`  
  - `api/lib/inspection-engine.js`, `api/lib/normalize.js`  
- **유지(Phase 2까지)**: `api/lib/rules-data.js`, `api/config/db.js`, `api/scripts/seed.js`, `api/scripts/check-db.js`, `api/scripts/test-inspect-save.js` (시드·도구 이전 완료 후 삭제).

### 5.3 Phase 2 — 시드·도구 PHP(및 SQL) 이전 후 Node 제거

- **시드**: `api/scripts/seed.php` (CLI) 또는 `docs/sql/` 시드 SQL 확장. risk_taxonomy·quizzes 등은 PHP가 engine/·JSON·DB에서 읽어 INSERT 하거나 SQL 고정.
- **check-db**: `api/scripts/check-db.php` (CLI) 또는 배포 가이드에서 “api/health + DB 접속 확인”으로 대체.
- **test-inspect-save**: PHP CLI 또는 수동 검증 절차로 대체.
- **삭제**:  
  - `api/scripts/seed.js`, `check-db.js`, `test-inspect-save.js`  
  - `api/lib/rules-data.js`, `api/config/db.js`  
  - `api/package.json`, `api/package-lock.json`, `api/node_modules/` (프로젝트 전역에서 Node 불필요하다고 확정 시).

### 5.4 문서

- **api/README.md**, **실행_순서_가이드.md**: “**운영 시 Node·Express 불필요. PHP(필요 시 Python), OpenAPI. 시드·진단은 PHP 또는 SQL.**” 반영.
- **배포 체크리스트**: Node·npm 제거, PHP·Apache·MySQL만 명시.

---

## 6. 다음 액션 — Cursor 플랜 모드 구현 예정

1. **회의 확정**: 위 결론(§5) 및 Phase 1·2 순서 확정.  
2. **플랜 수립**: Cursor **플랜 모드**에서 Phase 1(삭제) → Phase 2(시드·도구 이전) 작업 항목 정리.  
3. **구현**:  
   - Phase 1: server.js, routes, lib(inspection-engine, normalize) 삭제.  
   - Phase 2: seed.php(또는 SQL)·check-db.php(또는 가이드)·test 대체 구현 후 Node 스크립트·package·node_modules 제거.  
4. **문서**: README·실행 가이드·배포 문서를 PHP·OpenAPI 기준으로 수정.

---

**문서 끝** (회의에서 §5 확정 후, `meetings/progress/` 에 진행상황 기록. 이어서 **Cursor 플랜 모드**로 구현 진행.)
