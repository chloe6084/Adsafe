-- AdSafe v2 마이그레이션: 크레딧 시스템 + AI 생성 이력
-- 기존 adsafe_2 스키마에 추가 실행

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE adsafe_2;

-- ---------------------------------------------------------------------------
-- E. 크레딧 시스템
-- ---------------------------------------------------------------------------

-- 20) credit_plans — 요금제 정의
CREATE TABLE IF NOT EXISTS credit_plans (
  plan_id BIGINT NOT NULL AUTO_INCREMENT,
  plan_code VARCHAR(30) NOT NULL,
  plan_name VARCHAR(80) NOT NULL,
  description TEXT,
  -- 일일 사용 한도
  daily_inspect_limit INT DEFAULT 5 COMMENT '일일 검수 횟수 (-1=무제한)',
  daily_quiz_limit INT DEFAULT 3 COMMENT '일일 퀴즈 세션 수 (-1=무제한)',
  daily_ai_generate_limit INT DEFAULT 0 COMMENT '일일 AI 생성 횟수 (-1=무제한)',
  history_view_limit INT DEFAULT 10 COMMENT '검수 이력 조회 건수 (-1=무제한)',
  -- 크레딧 관련
  monthly_credits INT DEFAULT 0 COMMENT '월 제공 크레딧',
  price_monthly DECIMAL(10,2) DEFAULT 0.00,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (plan_id),
  UNIQUE KEY uq_credit_plans_code (plan_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기본 요금제 삽입
INSERT IGNORE INTO credit_plans (plan_code, plan_name, description, daily_inspect_limit, daily_quiz_limit, daily_ai_generate_limit, history_view_limit, monthly_credits, price_monthly) VALUES
('free', '무료', '기본 무료 플랜', 5, 3, 1, 10, 0, 0.00),
('pro', 'Pro', '프로 유료 플랜', -1, -1, 50, -1, 100, 29000.00),
('admin', '관리자', '관리자 전용 (무제한)', -1, -1, -1, -1, -1, 0.00);

-- 21) user_credits — 사용자별 크레딧 잔액
CREATE TABLE IF NOT EXISTS user_credits (
  user_credit_id BIGINT NOT NULL AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  plan_code VARCHAR(30) DEFAULT 'free',
  credit_balance INT DEFAULT 0 COMMENT '잔여 크레딧',
  -- 일일 사용 카운터 (매일 리셋)
  daily_inspect_used INT DEFAULT 0,
  daily_quiz_used INT DEFAULT 0,
  daily_ai_used INT DEFAULT 0,
  last_daily_reset DATE DEFAULT NULL COMMENT '마지막 일일 리셋 날짜',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_credit_id),
  UNIQUE KEY uq_user_credits_user (user_id),
  INDEX idx_user_credits_plan (plan_code),
  CONSTRAINT fk_user_credits_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22) credit_transactions — 크레딧 사용/충전 이력
CREATE TABLE IF NOT EXISTS credit_transactions (
  transaction_id BIGINT NOT NULL AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  type ENUM('charge','use','refund','admin_grant','plan_change') NOT NULL,
  amount INT NOT NULL COMMENT '양수=충전, 음수=사용',
  balance_after INT DEFAULT 0 COMMENT '거래 후 잔액',
  description VARCHAR(255) DEFAULT NULL,
  feature VARCHAR(50) DEFAULT NULL COMMENT 'inspect, quiz, ai_generate 등',
  reference_id VARCHAR(100) DEFAULT NULL COMMENT '관련 ID (run_id, attempt_id 등)',
  created_by BIGINT DEFAULT NULL COMMENT '관리자 부여 시 관리자 ID',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (transaction_id),
  INDEX idx_credit_tx_user (user_id),
  INDEX idx_credit_tx_type (type),
  INDEX idx_credit_tx_feature (feature),
  INDEX idx_credit_tx_created (created_at),
  CONSTRAINT fk_credit_tx_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- F. AI 광고문구 생성 이력
-- ---------------------------------------------------------------------------

-- 23) ai_generations — AI 생성 이력
CREATE TABLE IF NOT EXISTS ai_generations (
  generation_id BIGINT NOT NULL AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  workspace_id BIGINT DEFAULT 1,
  -- 입력
  ad_type VARCHAR(80) DEFAULT NULL COMMENT '광고 유형',
  target_audience VARCHAR(255) DEFAULT NULL COMMENT '타겟 고객',
  keywords TEXT COMMENT '강조 포인트/키워드',
  additional_info TEXT COMMENT '추가 정보',
  user_prompt TEXT COMMENT '사용자 입력 프롬프트',
  -- AI 응답
  ai_response TEXT COMMENT 'AI가 생성한 전체 응답',
  model_used VARCHAR(50) DEFAULT NULL COMMENT '사용된 AI 모델',
  prompt_tokens INT DEFAULT 0,
  completion_tokens INT DEFAULT 0,
  total_tokens INT DEFAULT 0,
  -- 메타
  status ENUM('pending','completed','failed') DEFAULT 'completed',
  error_message TEXT DEFAULT NULL,
  processing_ms INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (generation_id),
  INDEX idx_ai_gen_user (user_id),
  INDEX idx_ai_gen_workspace (workspace_id),
  INDEX idx_ai_gen_status (status),
  INDEX idx_ai_gen_created (created_at),
  CONSTRAINT fk_ai_gen_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_gen_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (workspace_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'AdSafe v2 마이그레이션 (크레딧+AI) 완료.' AS message;
