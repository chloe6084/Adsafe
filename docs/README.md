# AdSafe 문서 (docs) 구조

문서는 **회의**, **보고**, **가이드**, **스키마/SQL** 로 구분하여 관리합니다.

---

## 폴더별 용도

| 폴더 | 용도 | 비고 |
|------|------|------|
| **meetings/** | 회의 관련 | 실행 전 아젠다, 회의 진행상황 |
| **meetings/agenda/** | 실행 전 회의 아젠다 | 결론 도출 전 논의·결정 사항 정리 |
| **meetings/progress/** | 회의 진행상황 | 회의 후 결론, 결정 사항, 액션 추적 |
| **reports/** | 보고서 | 수정·배포 완료 보고 등 |
| **reports/completed/** | 수정완료 보고 | CTO/기술 보고서, 변경 완료 내역 |
| **guides/** | 운영·검증 가이드 | 테스트, 배포, 운영 절차 |
| **guides/test/** | 검수 테스트 가이드 | 실사이트 검수·이력 검증 방법 |
| **sql/** | DB 스키마·시드·ERD | 스키마, 시드 스크립트, 연결 가이드 |

---

## 루트에 두는 문서

- **README.md** (본 파일): docs 구조 안내.
- **git-workflow-vlackholism5.md**: Git 브랜치·워크플로 공통 가이드 (프로젝트 전역).

---

## 파일 배치 요약

- **실행 전 회의 필요 사항** → `meetings/agenda/`
- **회의 후 진행상황·결정 반영** → `meetings/progress/`
- **수정완료·기술 보고** → `reports/completed/`
- **검수 테스트 가이드** → `guides/test/`
- **DB 스키마·시드·ERD** → `sql/`

docs/
├── README.md                    ← 폴더 구조 안내 (신규)
├── git-workflow-vlackholism5.md ← 루트 유지 (프로젝트 공통)
│
├── meetings/                     ← 회의
│   ├── README.md
│   ├── agenda/                  ← 실행 전 회의 아젠다
│   │   └── 멀티롤_회의_검수이력_DB관리_아젠다.md
│   └── progress/                ← 회의 진행상황
│       └── README.md
│
├── reports/                     ← 보고서
│   ├── README.md
│   └── completed/               ← 수정완료 보고
│       └── CTO_보고서_JS검수엔진제거 및 이벤트룰 DB이전.md
│
├── guides/                      ← 가이드
│   ├── README.md
│   └── test/                    ← 검수 테스트 가이드
│       └── 검수_실사이트_테스트_가이드.md
│
└── sql/                         ← 기존 유지 (스키마·시드·ERD)