# CTO 팀 기술 보고서

---

| 항목 | 내용 |
|------|------|
| **문서번호** | CTO-RPT-ADSAFE-2026-002 |
| **제목** | AdSafe Node·Express 제거 및 시드·도구 PHP 이전 |
| **작성일** | 2026년 2월 13일 |
| **작성팀** | CTO (Cursor Tech / Product) |
| **관련 프로젝트** | AdSafe MVP |
| **관련 아젠다** | Node_Express_잔존_정책_아젠다.md, Node Express 제거 실행 플랜 |

---

## 1. 개요

운영 스택을 **PHP + 필요 시 Python + OpenAPI** 로 통일하고 **Node·Express를 사용하지 않기**로 한 정책에 따라, `api/` 내 Node(Express) 서버·라우트·엔진을 삭제하고, 시드·DB 진단·검수 저장 테스트를 **PHP CLI 스크립트**로 이전하였다. 배포 시 **Node·npm 설치가 불필요**하며, 문서 및 프론트 안내 문구를 PHP 기준으로 수정하였다.

---

## 2. 배경 및 목적

### 2.1 배경

- AdSafe API는 초기에 Node + Express로 구축되었으나, 이후 **PHP**(`api/index.php`, handlers, engine)로 검수·이력 API가 이전되어 **실제 런타임은 PHP만** 사용 중이었음.
- Node는 **시드**(`scripts/seed.js`), **DB 진단**(`check-db.js`), **검수 저장 테스트**(`test-inspect-save.js`) 등 도구 용도로만 잔존하여, 배포·온보딩 시 Node 설치가 필요했음.
- 정책상 **운영 시 Node·Express 불필요, PHP·필요 시 Python·OpenAPI만 사용**하기로 함.

### 2.2 목적

- **Phase 1**: 사용하지 않는 Node API 서버·라우트·엔진 파일 삭제.
- **Phase 2**: 시드·check-db·test-inspect-save를 **PHP CLI**로 이전 후, Node 스크립트·package·node_modules 제거.
- **Phase 3**: README·실행 가이드·프론트/API 안내 문구를 **PHP 명령 기준**으로 통일.

---

## 3. 작업 범위

| 구분 | 범위 | 비고 |
|------|------|------|
| **Phase 1** | server.js, routes 2건, lib/inspection-engine.js, lib/normalize.js 삭제, api/routes 폴더 제거 | 즉시 삭제 가능 (PHP에 동일 기능 있음) |
| **Phase 2** | env_pdo.php 추가, seed.php·check-db.php·test-inspect-save.php 작성, adusafe-questions.json 생성, Node 스크립트·lib/rules-data.js·config/db.js·package.json·node_modules 삭제 | 시드·도구 PHP 이전 후 Node 제거 |
| **Phase 3** | main.js, adusafe-start.html, users.php, 실행_순서_가이드.md, api/README.md, 검수_실사이트_테스트_가이드.md 수정 | 문구·절차 PHP 기준 |
| **유지** | HTML, JS, MD, CSS (프론트·문서), PHP API·engine | UI/UX·API 동작 변경 없음 |

---

## 4. 수행 내용

### 4.1 Phase 1 — Node API·엔진·라우트 제거

| 단계 | 작업 | 상세 |
|------|------|------|
| 1-1 | 파일 삭제 | `api/server.js` |
| 1-2 | 파일 삭제 | `api/routes/inspect.js`, `api/routes/inspection-history.js` |
| 1-3 | 파일 삭제 | `api/lib/inspection-engine.js`, `api/lib/normalize.js` |
| 1-4 | 폴더 정리 | `api/routes/` (빈 폴더) 삭제 |

**결과**: Node 서버·라우트·엔진 제거. 검수·이력 API는 기존대로 PHP만 담당.

---

### 4.2 Phase 2 — 시드·도구 PHP 이전 및 Node 잔여물 제거

#### 4.2.1 CLI용 env·PDO 로더 (api/lib/env_pdo.php)

- **목적**: `bootstrap.php`는 `header()` 호출로 웹 전용이므로, CLI(seed, check-db)에서 사용할 수 있는 **헤더 출력 없는** env·PDO 로더 추가.
- **내용**: `load_env_file`, `pdo_from_env`, `get_pdo_cli()` 정의. 경로 `__DIR__ . '/../.env'` 기준.

#### 4.2.2 시드 데이터·스크립트

- **api/data/adusafe-questions.json**  
  - `js/adusafe-questions.js`의 ADU_QUESTIONS 배열을 JSON으로 옮긴 파일.  
  - 필드: riskCode, stem, options, correctIndex, explanation, suggestion.
- **api/scripts/seed.php**  
  - **workspaces**(1), **users**(1, 비밀번호 `hash('sha256', ...)`), **risk_taxonomy**, **rule_set_versions**(v1.0.0), **rules**, **quizzes**, **quiz_choices** INSERT.  
  - 데이터 소스: `api/engine/rules_data.json`, `api/data/adusafe-questions.json`.  
  - 실행: `php api/scripts/seed.php` (프로젝트 루트 또는 api 폴더에서).

#### 4.2.3 DB 연결 진단 (api/scripts/check-db.php)

- **역할**: .env 존재·키 목록(DB_PASSWORD 마스킹), DB_SSL_CA 경로·존재, MySQL 연결 시도 후 성공/실패 출력.
- **실행**: `php api/scripts/check-db.php`.

#### 4.2.4 검수 저장 테스트 (api/scripts/test-inspect-save.php)

- **역할**: `POST {BASE_URL}/api/inspect` 호출 후 응답에 `saveError` 유무 출력.  
  - BASE_URL은 `.env`의 `BASE_URL` 또는 기본 `http://localhost/AdSafe`.
- **실행**: `php api/scripts/test-inspect-save.php` (Apache 실행 중일 때 유효).

#### 4.2.5 Node 잔여물 삭제

- **삭제 파일**:  
  - `api/scripts/seed.js`, `check-db.js`, `test-inspect-save.js`  
  - `api/lib/rules-data.js`, `api/config/db.js`  
  - `api/package.json`, `api/package-lock.json`  
- **삭제 폴더**: `api/node_modules/`, `api/config/` (db.js만 있던 폴더).

**결과**: 운영·배포 시 **Node·npm 불필요**. 시드·진단·저장 테스트는 PHP CLI로만 실행.

---

### 4.3 Phase 3 — 문서·문구 수정

| 대상 | 변경 내용 |
|------|------------|
| **main.js** (약 79행, 174행) | `"node scripts/seed.js"` → `"php api/scripts/seed.php"` |
| **adusafe-start.html** (약 65행) | `"시드(npm run seed)"` → `"시드(php api/scripts/seed.php)"` |
| **api/handlers/users.php** (약 135행) | 에러 메시지 `"npm run seed"` → `"php api/scripts/seed.php"` |
| **api/실행_순서_가이드.md** | Node·npm 전제 제거. 시드·check-db·test-inspect-save를 PHP 명령 기준으로 전면 수정. |
| **api/README.md** | 시드 실행을 `php api/scripts/seed.php`로 수정. Node·Express 미사용 문구 반영. |
| **docs/guides/test/검수_실사이트_테스트_가이드.md** | 시드 관련 `npm run seed` → `php api/scripts/seed.php` 반영. |

---

## 5. 결과 및 검증

### 5.1 변경 파일 요약

| 유형 | 경로 |
|------|------|
| **삭제** | api/server.js, api/routes/inspect.js, api/routes/inspection-history.js, api/lib/inspection-engine.js, api/lib/normalize.js |
| **삭제** | api/scripts/seed.js, check-db.js, test-inspect-save.js, api/lib/rules-data.js, api/config/db.js, api/package.json, api/package-lock.json, api/config/, api/node_modules/ |
| **신규** | api/lib/env_pdo.php, api/data/adusafe-questions.json, api/scripts/seed.php, api/scripts/check-db.php, api/scripts/test-inspect-save.php |
| **수정** | main.js, adusafe-start.html, api/handlers/users.php |
| **수정** | api/실행_순서_가이드.md, api/README.md, docs/guides/test/검수_실사이트_테스트_가이드.md |

### 5.2 검증 권장 절차

- **Phase 1 후**: `http://localhost/AdSafe/api/health` 및 검수·이력 API가 PHP로 정상 동작하는지 확인.
- **Phase 2 후**:  
  - `php api/scripts/check-db.php` → 연결 성공 메시지 확인.  
  - `php api/scripts/seed.php` → workspaces, users, risk_taxonomy, rules, quizzes INSERT 확인.  
  - (선택) Apache 실행 후 `php api/scripts/test-inspect-save.php` → saveError 없음·runId 확인.  
  - 검수 실행 후 이력 목록·상세에서 저장 정상 여부 확인.
- **Phase 3 후**: 문서·프론트 문구에 Node/npm 언급이 없고, 시드·진단·테스트 명령이 PHP 기준인지 확인.

### 5.3 기대 효과

- **배포 단순화**: Apache + PHP + MySQL만으로 실행·시드·진단 가능. Node 설치 불필요.
- **정책 일관**: 운영 스택을 PHP(필요 시 Python)·OpenAPI로 통일.
- **유지보수**: 시드·도구가 PHP로 통일되어, 룰·택소노미 데이터는 `rules_data.json`, `adusafe-questions.json` 및 DB와만 연동.

---

## 6. 부록

### 6.1 참조 문서

- 아젠다: `docs/meetings/agenda/Node_Express_잔존_정책_아젠다.md`
- 플랜: Node Express 제거 실행 플랜 (Phase 1·2·3, 검증)
- 검수 테스트 가이드: `docs/guides/test/검수_실사이트_테스트_가이드.md`
- 실행 순서 가이드: `api/실행_순서_가이드.md`

### 6.2 시드·진단·테스트 실행 방법

```bash
# 프로젝트 루트에서 (c:\xampp\htdocs\Adsafe 등)
php api/scripts/check-db.php    # DB 연결 진단
php api/scripts/seed.php       # 시드 실행 (최초 1회 또는 필요 시)
php api/scripts/test-inspect-save.php   # 검수 저장 테스트 (Apache 실행 중일 때)
```

---

**보고서 끝**
