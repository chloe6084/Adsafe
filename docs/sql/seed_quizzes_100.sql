-- AdSafe AduSafe 퀴즈 시드: 85개 추가 (기존 15 + 85 = 총 100문제)
-- workspace_id = 1 기준

SET NAMES utf8mb4;
USE adsafe_2;

-- ============================================================
-- 과장표현 (RISK_SUPERLATIVE_*) — 12문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_ABSOLUTE', 'easy', '다음 중 의료광고에서 사용할 수 없는 절대적 최상급 표현은?', '\"최고\", \"유일\", \"완벽\" 등 절대적 최상급 표현은 객관적 입증이 어렵습니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '우수한 의료 서비스', 0), (@qid, 1, '국내 최고의 피부과', 1), (@qid, 2, '경험이 풍부한 의료진', 0), (@qid, 3, '다양한 진료 프로그램', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_ABSOLUTE', 'normal', '\"완벽한 시술\"을 안전하게 대체할 수 있는 표현은?', '\"완벽\"은 절대적 최상급이므로 \"정밀한\", \"꼼꼼한\" 등으로 대체합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '궁극의 시술', 0), (@qid, 1, '정밀하고 꼼꼼한 시술', 1), (@qid, 2, '끝판왕 시술', 0), (@qid, 3, '무결점 시술', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_ABSOLUTE', 'hard', '다음 광고 문구 중 의료법 위반 소지가 가장 적은 것은?', '구체적 수치나 객관적 근거가 있는 표현이 안전합니다.', '의료법 시행령 제24조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '최고의 의료진이 함께합니다', 0), (@qid, 1, '20년 경력의 전문의가 직접 진료합니다', 1), (@qid, 2, '유일무이한 치료법', 0), (@qid, 3, '완벽한 결과를 보장합니다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_RANK', 'easy', '\"지역 1위 병원\"이라고 광고하려면 반드시 필요한 것은?', '순위 주장에는 반드시 객관적 근거(조사기관, 조사기간 등)가 필요합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '원장님의 자기 확신', 0), (@qid, 1, '공인된 기관의 조사 근거', 1), (@qid, 2, '환자 수 통계', 0), (@qid, 3, '직원들의 동의', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_RANK', 'normal', '다음 중 순위 주장 시 명시해야 할 정보가 아닌 것은?', '조사 기관, 조사 기간, 조사 대상 범위를 명시해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '조사 기관명', 0), (@qid, 1, '조사 기간', 0), (@qid, 2, '원장의 학력', 1), (@qid, 3, '조사 대상 범위', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_MARKETING', 'easy', '\"끝판왕\", \"명품\", \"마스터\" 같은 수식어가 제한되는 이유는?', '극대화 수식어는 과장 표현에 해당하여 소비자 오인을 유발할 수 있습니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '외래어이기 때문', 0), (@qid, 1, '과장으로 소비자 오인을 유발할 수 있어서', 1), (@qid, 2, '의학 용어가 아니기 때문', 0), (@qid, 3, '다른 병원도 사용하기 때문', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_FIRST', 'normal', '\"국내 최초 도입\" 광고가 합법이 되려면?', '최초 주장은 특허, 인증서 등 객관적 입증 자료가 필요합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '직원 교육만 하면 됨', 0), (@qid, 1, '특허 또는 인증서 등 객관적 근거 제시', 1), (@qid, 2, '홈페이지에 공지하면 됨', 0), (@qid, 3, '구두로 설명하면 됨', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_FIRST', 'hard', '다음 중 \"최초\" 주장이 허용될 가능성이 가장 높은 경우는?', '특허 등록 등 공신력 있는 근거가 있으면 허용될 수 있습니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '원장이 먼저 시작했다고 주장', 0), (@qid, 1, '특허청 등록 특허 번호를 명시', 1), (@qid, 2, '인터넷 검색 결과로 증명', 0), (@qid, 3, '환자 후기로 증명', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_MARKETING', 'normal', '\"프리미엄 진료\"를 안전하게 표현하는 방법은?', '구체적인 서비스 내용을 설명하는 것이 안전합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '하이엔드 VIP 진료', 0), (@qid, 1, '1:1 맞춤 상담과 정밀 검진을 제공합니다', 1), (@qid, 2, '명품 진료 서비스', 0), (@qid, 3, '올인원 토탈 솔루션', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_ABSOLUTE', 'normal', '\"넘사벽 기술력\"이라는 광고 표현의 문제점은?', '\"넘사벽\"은 절대적 우위를 암시하는 과장 표현입니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '은어를 사용했기 때문', 0), (@qid, 1, '절대적 우위를 암시하는 과장 표현이기 때문', 1), (@qid, 2, '숫자가 없기 때문', 0), (@qid, 3, '영어가 아니기 때문', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_RANK', 'hard', '\"만족도 1위\" 광고에서 꼭 밝혀야 하는 3가지는?', '순위 광고에는 조사기관, 조사기간, 조사대상을 반드시 명시해야 합니다.', '의료법 시행령', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '조사기관, 병원이름, 가격', 0), (@qid, 1, '조사기관, 조사기간, 조사대상', 1), (@qid, 2, '병원주소, 전화번호, 원장이름', 0), (@qid, 3, '가격, 할인율, 이벤트기간', 0);

-- ============================================================
-- 효과보장 (RISK_GUARANTEE_*) — 12문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_RESULT', 'easy', '\"시술 후 주름이 완전히 사라집니다\"가 위반인 이유는?', '결과를 단정하는 표현은 의료광고에서 금지됩니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '주름이라는 단어를 사용해서', 0), (@qid, 1, '결과를 \"완전히\"라고 단정했기 때문', 1), (@qid, 2, '시술이라는 단어를 사용해서', 0), (@qid, 3, '가격을 명시하지 않아서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_RESULT', 'normal', '\"탈모 고민, 확실하게 해결해 드립니다\"를 안전하게 바꾸면?', '결과 단정 대신 가능성을 표현해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '탈모 완치 보장!', 0), (@qid, 1, '탈모 고민, 전문 상담으로 개선에 도움을 드립니다', 1), (@qid, 2, '탈모 100% 해결 가능', 0), (@qid, 3, '탈모 없애드립니다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_ZERO_RISK', 'easy', '\"부작용 걱정 없이 안전하게\"라는 표현이 위험한 이유는?', '어떤 시술이든 부작용 가능성을 완전히 부정할 수 없습니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '안전이라는 단어를 써서', 0), (@qid, 1, '부작용 가능성을 완전히 부정하기 때문', 1), (@qid, 2, '걱정이라는 감정을 언급해서', 0), (@qid, 3, '가격이 명시되지 않아서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_ZERO_RISK', 'normal', '\"통증 없는 임플란트\"를 안전하게 표현하면?', '부작용/통증 부정 대신 \"최소화\"를 사용합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '무통 100% 보장 임플란트', 0), (@qid, 1, '통증을 최소화하는 임플란트 시술', 1), (@qid, 2, '출혈 없는 무통 임플란트', 0), (@qid, 3, '아프지 않은 임플란트', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_CERTAINTY', 'easy', '\"100% 효과 보장\"이 제한되는 이유는?', '의료 행위의 결과는 개인차가 있어 100% 단정이 불가합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '숫자를 사용했기 때문', 0), (@qid, 1, '의료 결과는 개인차가 있어 100% 단정할 수 없기 때문', 1), (@qid, 2, '보장이라는 단어가 법률 용어라서', 0), (@qid, 3, '효과라는 단어가 모호해서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_CERTAINTY', 'normal', '\"반드시 효과를 보실 수 있습니다\"를 대체할 표현은?', '\"반드시\"는 확정적 표현이므로 \"많은 분들이\" 등으로 완화합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '무조건 효과 있습니다', 0), (@qid, 1, '많은 분들이 만족하고 계십니다', 1), (@qid, 2, '확실한 효과를 약속합니다', 0), (@qid, 3, '완전한 결과를 보장합니다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_RESPONSIBILITY', 'easy', '\"끝까지 책임집니다\"라는 광고 표현이 위험한 이유는?', '무한 책임을 암시하는 표현은 과장 광고에 해당합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '친절한 느낌이 들어서', 0), (@qid, 1, '법적 범위를 넘는 무한 책임을 암시하기 때문', 1), (@qid, 2, '병원 이름이 빠져서', 0), (@qid, 3, '진료과가 명시되지 않아서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_RESPONSIBILITY', 'hard', '다음 중 \"책임 진료\" 표현을 안전하게 대체한 것은?', '구체적인 사후관리 내용을 설명하는 것이 안전합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '무한 책임 진료', 0), (@qid, 1, '시술 후 정기적인 경과 확인을 진행합니다', 1), (@qid, 2, '결과 보장 진료', 0), (@qid, 3, '약속 진료', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_RESULT', 'hard', '다음 중 결과 보장 표현이 아닌 것은?', '가능성을 열어두는 표현은 결과 보장에 해당하지 않습니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '완치를 약속드립니다', 0), (@qid, 1, '개선에 도움이 될 수 있습니다', 1), (@qid, 2, '해결해 드립니다', 0), (@qid, 3, '없애드립니다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_ZERO_RISK', 'hard', '\"재발 없는 치료\"가 위반인 이유와 대안을 올바르게 짝지은 것은?', '재발 가능성을 완전히 부정할 수 없으므로 \"재발률을 낮추는\" 등으로 표현합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '위반 이유: 치료 단어 사용 / 대안: 시술로 변경', 0), (@qid, 1, '위반 이유: 재발 부정 단정 / 대안: 재발률을 낮추는 치료', 1), (@qid, 2, '위반 이유: 가격 미표시 / 대안: 가격 추가', 0), (@qid, 3, '위반 이유: 기간 미명시 / 대안: 기간 추가', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_CERTAINTY', 'hard', '다음 표현들 중 확정성 위반에 해당하지 않는 것은?', '\"도움이 될 수 있습니다\"는 가능성을 열어둔 표현입니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '반드시 좋아집니다', 0), (@qid, 1, '무조건 해결됩니다', 0), (@qid, 2, '확실하게 달라집니다', 0), (@qid, 3, '증상 개선에 도움이 될 수 있습니다', 1);

-- ============================================================
-- 치료과정 (RISK_DURATION_*) — 8문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_DURATION_FIXED', 'easy', '\"3일 만에 완치!\"가 위반인 이유는?', '치료 기간을 단정하면 개인차를 무시하는 과장 광고입니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '3이라는 숫자가 문제', 0), (@qid, 1, '치료 기간을 단정하여 개인차를 무시하기 때문', 1), (@qid, 2, '완치라는 의학 용어를 사용해서', 0), (@qid, 3, '느낌표를 사용해서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_DURATION_FIXED', 'normal', '치료 기간 관련 광고에서 반드시 포함해야 하는 문구는?', '개인차가 있음을 반드시 안내해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '병원 전화번호', 0), (@qid, 1, '\"치료 기간은 개인에 따라 다를 수 있습니다\"', 1), (@qid, 2, '원장 이름', 0), (@qid, 3, '시술 가격', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_DURATION_INSTANT', 'easy', '\"바로 효과를 보실 수 있습니다\"가 제한되는 이유는?', '즉시 효과를 단정하면 소비자 오인을 유발합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '\"바로\"라는 부사가 불법이라서', 0), (@qid, 1, '즉시 효과를 단정하여 소비자 오인을 유발하기 때문', 1), (@qid, 2, '효과라는 단어가 모호해서', 0), (@qid, 3, '존칭을 사용했기 때문', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_DURATION_INSTANT', 'normal', '\"당일 퇴원 가능\"을 안전하게 표현하면?', '조건을 함께 안내하는 것이 안전합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '무조건 당일 퇴원', 0), (@qid, 1, '경과에 따라 당일 퇴원이 가능할 수 있습니다', 1), (@qid, 2, '즉시 퇴원 보장', 0), (@qid, 3, '입원 불필요', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_DURATION_SIMPLICITY', 'easy', '\"간단하게 해결!\"이라는 표현이 제한되는 이유는?', '치료를 과도하게 단순화하면 환자가 위험성을 간과할 수 있습니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '해결이라는 단어가 모호해서', 0), (@qid, 1, '치료를 과도하게 단순화하여 위험성을 간과하게 만들 수 있어서', 1), (@qid, 2, '느낌표를 사용해서', 0), (@qid, 3, '간단이라는 형용사가 불법이라서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_DURATION_SIMPLICITY', 'normal', '\"부담 없이 쉽게\"를 안전하게 대체하면?', '\"상담 후 결정\"을 함께 안내하는 것이 좋습니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '아무 걱정 없이 편하게', 0), (@qid, 1, '전문의 상담 후 나에게 맞는 방법을 결정하세요', 1), (@qid, 2, '초간단 해결법', 0), (@qid, 3, '원스톱으로 끝', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_DURATION_FIXED', 'hard', '다음 중 치료 기간 광고로 가장 안전한 표현은?', '개인차를 명시하고 상담을 권유하는 것이 안전합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '1주일 완성 프로그램', 0), (@qid, 1, '개인 상태에 따라 치료 기간이 달라질 수 있으며, 상담을 통해 안내드립니다', 1), (@qid, 2, '한 달이면 끝', 0), (@qid, 3, '2주 완치 코스', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_DURATION_INSTANT', 'hard', '레이저 시술 광고에서 \"즉각적인 피부 개선\"을 안전하게 표현하면?', '시술 후 효과가 점진적으로 나타남을 안내합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '바로 효과! 즉각 개선!', 0), (@qid, 1, '시술 후 점진적으로 피부 개선 효과를 기대할 수 있습니다', 1), (@qid, 2, '당일 피부 완성', 0), (@qid, 3, '즉시 동안 피부', 0);

-- ============================================================
-- 공포조성 (RISK_FEAR_*) — 7문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_FEAR_DISEASE', 'easy', '\"방치하면 큰 병이 됩니다!\"가 제한되는 이유는?', '질병의 위험을 과장하여 공포를 조성하는 표현입니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '병이라는 단어를 사용해서', 0), (@qid, 1, '질병의 위험을 과장하여 공포를 조성하기 때문', 1), (@qid, 2, '느낌표를 사용해서', 0), (@qid, 3, '방치라는 단어가 불법이라서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_FEAR_DISEASE', 'normal', '공포 조성 대신 건전하게 검진을 권유하는 표현은?', '\"정기 검진을 권장합니다\" 등 건전한 안내가 적절합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '지금 안 하면 평생 후회합니다', 0), (@qid, 1, '건강을 위해 정기 검진을 권장합니다', 1), (@qid, 2, '방치하면 수술이 필요합니다', 0), (@qid, 3, '악화되면 되돌릴 수 없습니다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_FEAR_URGENT', 'easy', '\"더 늦기 전에 지금 바로!\"가 제한되는 이유는?', '과도한 시간 압박으로 소비자의 합리적 판단을 방해합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '\"지금\"이라는 부사가 불법이라서', 0), (@qid, 1, '과도한 시간 압박으로 합리적 판단을 방해하기 때문', 1), (@qid, 2, '\"바로\"라는 부사가 과장이라서', 0), (@qid, 3, '느낌표를 사용했기 때문', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_FEAR_URGENT', 'normal', '시급성을 적절하게 표현하는 방법은?', '전문의 상담을 권유하는 방식이 적절합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '지금 안 하면 늦습니다!', 0), (@qid, 1, '증상이 지속되면 전문의 상담을 받아보세요', 1), (@qid, 2, '서둘러야 합니다', 0), (@qid, 3, '시간이 없습니다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_FEAR_DISEASE', 'hard', '다음 중 공포 조성에 해당하지 않는 표현은?', '객관적 정보 전달은 공포 조성이 아닙니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '방치하면 위험합니다', 0), (@qid, 1, '정기적인 구강 검진으로 치아 건강을 관리하세요', 1), (@qid, 2, '악화됩니다', 0), (@qid, 3, '심각한 문제가 생깁니다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_FEAR_DISEASE', 'normal', '치과 광고에서 \"충치를 방치하면 발치까지\"라는 표현의 문제점은?', '최악의 결과를 강조하여 공포를 조성하는 표현입니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '치과 용어를 사용해서', 0), (@qid, 1, '최악의 결과를 강조하여 공포를 조성하기 때문', 1), (@qid, 2, '발치 비용을 명시하지 않아서', 0), (@qid, 3, '충치라는 질환명을 사용해서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_FEAR_URGENT', 'hard', '성형외과 광고에서 시급성을 사용하면 안 되는 주된 이유는?', '성형은 응급이 아닌 선택 진료이므로 시간 압박이 부적절합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '성형외과만 적용되는 규정이 있어서', 0), (@qid, 1, '선택 진료에 시간 압박을 가하면 합리적 판단을 방해하기 때문', 1), (@qid, 2, '성형 시술은 긴급하지 않으니까', 0), (@qid, 3, '미용은 의료가 아니라서', 0);

-- ============================================================
-- 외모조장 (RISK_APPEARANCE_*) — 5문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_APPEARANCE_SHAMING', 'easy', '\"두꺼운 허벅지가 고민이세요?\"가 제한되는 이유는?', '특정 신체 부위를 부정적으로 묘사하는 것은 외모 비하에 해당합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '허벅지라는 신체 부위를 언급해서', 0), (@qid, 1, '신체 부위를 부정적으로 묘사하여 외모 비하에 해당하기 때문', 1), (@qid, 2, '질문형 문장이라서', 0), (@qid, 3, '고민이라는 단어를 사용해서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_APPEARANCE_SHAMING', 'normal', '외모 관련 광고에서 안전한 접근 방식은?', '부정적 묘사 대신 긍정적 변화를 강조하는 것이 안전합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '현재 외모의 문제점을 부각', 0), (@qid, 1, '자신감 향상과 건강한 아름다움을 강조', 1), (@qid, 2, '이상적 체형과 비교', 0), (@qid, 3, '타인의 시선을 언급', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_APPEARANCE_IDEAL', 'easy', '\"여신 라인 완성\"이라는 표현이 제한되는 이유는?', '이상화된 외모를 조장하는 미화 표현은 과장에 해당합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '여신이라는 단어가 종교적이라서', 0), (@qid, 1, '이상화된 외모를 조장하는 과장 표현이기 때문', 1), (@qid, 2, '라인이라는 외래어를 사용해서', 0), (@qid, 3, '완성이라는 결과 단정이라서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_APPEARANCE_IDEAL', 'normal', '\"완벽한 몸매\"를 안전하게 표현하면?', '과도한 미화 대신 건강과 자연스러움을 강조합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '조각 같은 몸매', 0), (@qid, 1, '나에게 맞는 건강한 체형 관리', 1), (@qid, 2, '꿈의 바디라인', 0), (@qid, 3, '이상적인 S라인', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_APPEARANCE_SHAMING', 'hard', '다음 중 외모 비하에 해당하지 않는 표현은?', '긍정적이고 중립적인 표현은 외모 비하가 아닙니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '코끼리 다리에서 벗어나세요', 0), (@qid, 1, '건강하고 자신감 있는 모습을 응원합니다', 1), (@qid, 2, '흉한 흉터 제거', 0), (@qid, 3, '못난 이 얼굴을 바꿔드립니다', 0);

-- ============================================================
-- 경험담/후기 (RISK_EXPERIENCE_*) — 7문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_EXPERIENCE_REVIEW', 'easy', '환자 후기를 광고에 사용할 때 주의할 점은?', '전형적이지 않은 결과를 일반화하면 안 됩니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '가능한 많이 싣는다', 0), (@qid, 1, '\"개인 사례이며 결과는 다를 수 있습니다\" 문구를 포함한다', 1), (@qid, 2, '실명을 반드시 공개한다', 0), (@qid, 3, '사진을 꼭 함께 게시한다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_EXPERIENCE_BEFORE_AFTER', 'easy', '시술 전후 사진을 광고에 사용할 때 필요한 것은?', '전형적 사례임을 명시하고 개인차가 있음을 안내해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '포토샵으로 보정', 0), (@qid, 1, '전형적 사례임을 명시하고 개인차 안내', 1), (@qid, 2, '가장 극적인 사례만 선별', 0), (@qid, 3, '환자 동의 없이 사용', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_EXPERIENCE_BEFORE_AFTER', 'normal', '\"Before → After\" 사진 광고에서 반드시 병기해야 하는 문구는?', '개인차 안내와 전형적 사례 여부를 명시해야 합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '시술 가격', 0), (@qid, 1, '\"효과는 개인에 따라 다를 수 있습니다\"', 1), (@qid, 2, '촬영 장소', 0), (@qid, 3, '카메라 기종', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_EXPERIENCE_NARRATIVE', 'easy', '\"김○○님 치료 스토리\"를 광고에 사용할 때 주의점은?', '개별 사례를 일반화하면 과장에 해당할 수 있습니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '더 극적으로 각색한다', 0), (@qid, 1, '참고 수준임을 명시하고 결과 개인차를 안내한다', 1), (@qid, 2, '실명을 공개한다', 0), (@qid, 3, '동영상으로 촬영한다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_EXPERIENCE_REVIEW', 'normal', '\"리얼 후기! 인생 병원이에요~\"라는 후기 광고의 문제점은?', '과장된 후기는 소비자 오인을 유발할 수 있습니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '물결표를 사용해서', 0), (@qid, 1, '\"인생 병원\"은 과장이며 개인차 안내가 없기 때문', 1), (@qid, 2, '리얼이라는 외래어를 사용해서', 0), (@qid, 3, '느낌표를 사용해서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_EXPERIENCE_NARRATIVE', 'hard', '환자 경험담 광고에서 가장 안전한 형태는?', '개인차 안내와 함께 참고 수준임을 명시합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '\"이 시술로 인생이 바뀌었습니다\" - 환자 인터뷰', 0), (@qid, 1, '\"개인의 경험이며 결과는 다를 수 있습니다\" 안내와 함께 일반적인 만족도 언급', 1), (@qid, 2, '\"완치됐습니다!\" - 환자 후기', 0), (@qid, 3, '\"이 병원 아니면 안 됩니다\" - 환자 추천', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_EXPERIENCE_BEFORE_AFTER', 'hard', '전후 사진 광고가 합법이 되기 위한 필수 조건이 아닌 것은?', '환자 동의, 전형성 명시, 개인차 안내가 필수입니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '환자의 서면 동의', 0), (@qid, 1, '사진 보정 처리', 1), (@qid, 2, '전형적 사례임을 명시', 0), (@qid, 3, '개인차 안내 문구', 0);

-- ============================================================
-- 비교/비방 (RISK_COMPARISON_*) — 6문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_COMPARISON_OTHER', 'easy', '\"다른 병원과는 차원이 다릅니다\"가 제한되는 이유는?', '객관적 근거 없는 타 기관 비교는 제한됩니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '차원이라는 단어가 과학 용어라서', 0), (@qid, 1, '객관적 근거 없이 타 기관과 비교하기 때문', 1), (@qid, 2, '병원이라는 단어를 사용해서', 0), (@qid, 3, '비교급 문장이라서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_COMPARISON_OTHER', 'normal', '타 기관과 비교 광고가 허용되려면?', '객관적 데이터와 근거를 명시해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '환자 수만 비교하면 됨', 0), (@qid, 1, '공인된 조사 데이터와 출처를 명시', 1), (@qid, 2, '상대 병원 이름만 빼면 됨', 0), (@qid, 3, '주관적 평가로도 가능', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_COMPARISON_DISPARAGE', 'easy', '\"아무 데서나 받지 마세요\"라는 표현이 위반인 이유는?', '타 의료기관을 비하하는 표현은 제한됩니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '부정형 문장이라서', 0), (@qid, 1, '타 의료기관을 비하하는 표현이기 때문', 1), (@qid, 2, '\"받다\"라는 동사가 부적절해서', 0), (@qid, 3, '명령형 문장이라서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_COMPARISON_METHOD', 'normal', '\"우리만 가능한 특수 시술\"이 제한되는 이유는?', '방식의 우월성 주장은 객관적 근거가 필요합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '특수라는 단어가 군사 용어라서', 0), (@qid, 1, '유일성을 주장하려면 특허 등 객관적 근거가 필요하기 때문', 1), (@qid, 2, '시술이라는 단어를 사용해서', 0), (@qid, 3, '\"우리\"라는 1인칭을 사용해서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_COMPARISON_DISPARAGE', 'hard', '다음 중 타 기관 비방에 해당하지 않는 표현은?', '자신의 장점을 설명하는 것은 비방이 아닙니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '다른 곳에서 실패한 분들이 찾아옵니다', 0), (@qid, 1, '15년 경력의 전문의가 직접 상담합니다', 1), (@qid, 2, '일반 병원과는 급이 다릅니다', 0), (@qid, 3, '싼 게 비지떡입니다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_COMPARISON_METHOD', 'hard', '시술 방법 비교 광고가 합법적이려면?', '객관적 학술 근거와 구체적 차이를 명시해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '\"우리 것이 제일 좋다\"고만 표현', 0), (@qid, 1, '학술 논문 등 객관적 근거를 명시하며 구체적 차이점 설명', 1), (@qid, 2, '경쟁사 이름을 직접 거론', 0), (@qid, 3, '환자 후기로 비교', 0);

-- ============================================================
-- 유인/할인 (RISK_INDUCEMENT_*) — 8문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INDUCEMENT_DISCOUNT', 'easy', '\"무료 상담\" 광고를 할 때 주의해야 할 점은?', '무료 범위와 추가 비용 발생 가능성을 명시해야 합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '무료라는 단어는 무조건 금지', 0), (@qid, 1, '무료 범위와 추가 비용 가능성을 명시', 1), (@qid, 2, '상담만 무료면 문제 없음', 0), (@qid, 3, '무료 대신 0원으로 표기', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INDUCEMENT_DISCOUNT', 'normal', '할인 이벤트 광고에서 반드시 명시해야 하는 3가지는?', '이벤트 기간, 할인율, 적용 대상을 명시해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '원장이름, 전화번호, 주소', 0), (@qid, 1, '이벤트 기간, 할인율, 적용 대상(시술/진료)', 1), (@qid, 2, '할인금액, 병원규모, 직원수', 0), (@qid, 3, '원래가격, 할인가격, 병원연혁', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INDUCEMENT_CONDITION', 'easy', '\"선착순 50명 한정!\"이 제한되는 이유는?', '조건부 혜택의 기간과 내용이 불명확하면 제한됩니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '50이라는 숫자가 문제', 0), (@qid, 1, '한정 조건으로 과도한 유인을 하기 때문', 1), (@qid, 2, '선착순이라는 단어가 불법이라서', 0), (@qid, 3, '느낌표를 사용해서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INDUCEMENT_CONDITION', 'normal', '\"오늘만 특가!\"를 안전하게 바꾸면?', '구체적 기간과 조건을 명시하는 것이 안전합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '내일까지만 특가!', 0), (@qid, 1, '2월 한정 이벤트: ○○시술 20% 할인 (기간: 2/1~2/28)', 1), (@qid, 2, '지금 아니면 없어요!', 0), (@qid, 3, '마감 임박!', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INDUCEMENT_BUNDLE', 'easy', '패키지 상품 광고에서 명시해야 하는 것은?', '패키지 구성 항목과 개별 가격을 명확히 해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '패키지 이름만', 0), (@qid, 1, '구성 항목과 가격을 명확히 표시', 1), (@qid, 2, '할인율만', 0), (@qid, 3, '병원 이름만', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_PRICE_EXCESSIVE', 'easy', '50% 이상 할인 광고가 고위험인 이유는?', '과도한 할인은 의료 서비스의 질에 대한 우려를 야기합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '50이라는 숫자가 불법이라서', 0), (@qid, 1, '과도한 할인이 의료 서비스 질에 대한 우려를 야기하기 때문', 1), (@qid, 2, '%기호를 사용해서', 0), (@qid, 3, '할인이라는 단어가 불법이라서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_PRICE_EXCESSIVE', 'hard', '70% 할인 이벤트를 진행하고 싶다면 어떻게 해야 하나요?', '높은 할인율은 조건과 기간을 매우 명확히 해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '70% 할인은 무조건 불법이므로 불가', 0), (@qid, 1, '할인 적용 조건, 기간, 대상 시술을 매우 구체적으로 명시하고 심의를 받는다', 1), (@qid, 2, '69%로 낮추면 됨', 0), (@qid, 3, '할인 대신 \"특별가\"로 표현하면 됨', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INDUCEMENT_DISCOUNT', 'hard', '다음 이벤트 광고 중 가장 안전한 것은?', '기간, 할인율, 적용 대상이 모두 명시된 것이 안전합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '파격 할인! 지금 바로!', 0), (@qid, 1, '3월 개원기념: 스케일링 30% 할인 (3/1~3/31, 첫 방문 고객)', 1), (@qid, 2, '반값 이벤트 진행 중!', 0), (@qid, 3, '최저가 보장!', 0);

-- ============================================================
-- 자격/인증 (RISK_QUALIFICATION_*) — 6문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_QUALIFICATION_FALSE', 'easy', '\"인증 병원\"이라고 광고하려면?', '실제 인증을 받은 근거를 제시해야 합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '자체적으로 선언하면 됨', 0), (@qid, 1, '공인 기관의 인증서를 실제로 보유하고 근거를 제시', 1), (@qid, 2, '환자 추천이 있으면 됨', 0), (@qid, 3, '인터넷 평점이 높으면 됨', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_QUALIFICATION_FALSE', 'normal', '다음 중 허위 자격 광고에 해당하는 것은?', '실제 취득하지 않은 인증이나 자격을 표시하면 허위 광고입니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '실제 취득한 전문의 자격 표시', 0), (@qid, 1, '받지 않은 \"국제 인증 병원\" 표시', 1), (@qid, 2, '실제 수상한 의학상 표시', 0), (@qid, 3, '정식 개원 허가 표시', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_QUALIFICATION_MIXED', 'normal', '전문의와 비전문 과목을 함께 표기할 때 주의점은?', '전문의 자격과 비전문 과목을 명확히 구분해야 합니다.', '의료법 시행규칙', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '모든 과목을 전문의처럼 표기', 0), (@qid, 1, '전문의 자격 과목과 비전문 과목을 명확히 구분 표기', 1), (@qid, 2, '비전문 과목은 표시하지 않음', 0), (@qid, 3, '전문의를 생략하고 과목만 나열', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_QUALIFICATION_TITLE', 'easy', '\"명의\"라는 호칭을 광고에 사용하면 안 되는 이유는?', '임의 타이틀은 소비자 오인을 유발할 수 있습니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '명의는 사극에서만 쓰는 단어라서', 0), (@qid, 1, '공인되지 않은 임의 타이틀로 소비자 오인을 유발하기 때문', 1), (@qid, 2, '한자어라서', 0), (@qid, 3, '의사만 사용할 수 있어서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_QUALIFICATION_TITLE', 'normal', '의사를 소개할 때 안전한 표현은?', '정식 자격과 경력을 사실대로 표기하는 것이 안전합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '○○ 분야의 신', 0), (@qid, 1, '피부과 전문의 / ○○대학교 의학박사 / 15년 경력', 1), (@qid, 2, '전설의 명의', 0), (@qid, 3, '○○ 마스터 닥터', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_QUALIFICATION_MIXED', 'hard', '내과 전문의가 피부과 진료도 한다고 광고할 때 올바른 표기는?', '전문의 자격 과목과 추가 진료 과목을 명확히 분리해야 합니다.', '의료법 시행규칙', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '내과·피부과 전문의', 0), (@qid, 1, '내과 전문의 / 피부과 진료 가능', 1), (@qid, 2, '전문의(내과, 피부과)', 0), (@qid, 3, '종합 전문의', 0);

-- ============================================================
-- 보험오인 (RISK_INSURANCE_*) — 8문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INSURANCE_COVERAGE', 'easy', '\"실비 적용 가능\" 광고의 주의점은?', '보험 적용 조건은 개인마다 다르므로 조건을 안내해야 합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '실비라는 단어를 사용하면 안 됨', 0), (@qid, 1, '\"가입 조건에 따라 다를 수 있습니다\" 안내를 함께 표시', 1), (@qid, 2, '보험 회사 이름을 명시하면 됨', 0), (@qid, 3, '실비 대신 \"보험\"으로 표기', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INSURANCE_FREE', 'easy', '\"보험으로 무료 치료\"가 위반인 이유는?', '보험 급여에도 본인부담금이 있어 \"무료\"는 오인을 유발합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '보험이라는 단어를 사용해서', 0), (@qid, 1, '본인부담금이 있는데 \"무료\"라고 하면 소비자 오인을 유발하기 때문', 1), (@qid, 2, '치료라는 단어를 사용해서', 0), (@qid, 3, '무료라는 단어가 불법이라서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INSURANCE_FREE', 'normal', '\"0원 치료\"를 안전하게 표현하면?', '본인부담금 범위를 명시하는 것이 안전합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '완전 무료 진료', 0), (@qid, 1, '건강보험 적용 시 본인부담금이 적용됩니다 (조건에 따라 상이)', 1), (@qid, 2, '돈 한 푼 안 드는 치료', 0), (@qid, 3, '공짜 진료', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INSURANCE_SIMPLIFY', 'easy', '\"보험 자동 처리\"가 제한되는 이유는?', '보험 처리에는 필요 서류와 절차가 있으므로 자동이라고 할 수 없습니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '자동이라는 단어가 IT 용어라서', 0), (@qid, 1, '보험 처리에는 필요 서류와 절차가 있어 자동이 아니기 때문', 1), (@qid, 2, '보험회사와 협약이 없어서', 0), (@qid, 3, '자동처리가 기술적으로 불가능해서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INSURANCE_SIMPLIFY', 'normal', '보험 관련 안내를 올바르게 하는 방법은?', '필요 서류와 절차를 안내하는 것이 올바릅니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '알아서 다 해드립니다', 0), (@qid, 1, '보험 청구에 필요한 서류와 절차를 안내해 드립니다', 1), (@qid, 2, '서류 필요 없습니다', 0), (@qid, 3, '자동으로 처리됩니다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INSURANCE_GUARANTEE', 'easy', '\"보험금 100% 지급 보장\"이 위반인 이유는?', '보험금 지급은 보험사가 결정하며 병원이 보장할 수 없습니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '100%라는 숫자가 문제', 0), (@qid, 1, '보험금 지급은 보험사가 결정하며 병원이 보장할 수 없기 때문', 1), (@qid, 2, '보장이라는 단어가 보험 용어라서', 0), (@qid, 3, '지급이라는 단어가 금융 용어라서', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INSURANCE_GUARANTEE', 'hard', '보험 관련 광고에서 다음 중 가장 안전한 표현은?', '조건과 절차를 안내하는 것이 가장 안전합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '보험금 무조건 지급', 0), (@qid, 1, '보험 적용 여부는 가입 조건에 따라 다르며, 자세한 사항은 상담 시 안내드립니다', 1), (@qid, 2, '보험으로 완전 무료', 0), (@qid, 3, '보험 자동 처리 보장', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INSURANCE_COVERAGE', 'hard', '\"급여 적용\" 광고에서 함께 안내해야 하는 것은?', '비급여 항목, 본인부담금 등 추가 비용 가능성을 안내해야 합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '다른 병원의 가격', 0), (@qid, 1, '비급여 항목이 있을 수 있으며 본인부담금은 조건에 따라 상이함을 안내', 1), (@qid, 2, '보험사 연락처', 0), (@qid, 3, '의사 이력', 0);

-- ============================================================
-- 종합/기타 — 6문제
-- ============================================================

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_SUPERLATIVE_ABSOLUTE', 'hard', '의료광고 심의를 받지 않아도 되는 경우는?', '의료법상 일부 단순 정보 제공은 심의 대상이 아닐 수 있습니다.', '의료법 제57조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '할인 이벤트 광고', 0), (@qid, 1, '의료기관 명칭, 소재지, 진료과목 등 단순 정보 게시', 1), (@qid, 2, '시술 효과 광고', 0), (@qid, 3, '전후 사진 광고', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_RESULT', 'normal', '의료광고에서 \"~할 수 있습니다\"와 \"~합니다\"의 차이는?', '\"~할 수 있습니다\"는 가능성을, \"~합니다\"는 단정을 의미합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '차이가 없다', 0), (@qid, 1, '전자는 가능성 표현(안전), 후자는 결과 단정(위험)', 1), (@qid, 2, '후자가 더 안전하다', 0), (@qid, 3, '전자가 더 위험하다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_FEAR_DISEASE', 'hard', '다음 광고 문구 중 의료법 위반 소지가 가장 적은 것은?', '객관적 정보와 상담 권유가 가장 안전합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '방치하면 더 큰 병이 됩니다', 0), (@qid, 1, '궁금한 점은 전문의 상담을 통해 확인하세요', 1), (@qid, 2, '지금 안 하면 후회합니다', 0), (@qid, 3, '이 시술로 100% 해결됩니다', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_INDUCEMENT_DISCOUNT', 'normal', 'SNS 광고에서 \"이벤트\"라는 단어를 사용할 때 필수 표기 사항은?', '이벤트 기간, 할인 조건, 적용 대상을 명시해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '해시태그만 달면 됨', 0), (@qid, 1, '이벤트 기간, 할인 조건, 적용 대상 시술/진료를 명시', 1), (@qid, 2, '팔로우 유도 문구', 0), (@qid, 3, '좋아요 요청', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_COMPARISON_OTHER', 'hard', '의료기관 광고에서 자신의 강점을 합법적으로 표현하는 방법은?', '타 기관을 언급하지 않고 자체 강점을 객관적으로 설명합니다.', '의료법 제56조', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '다른 병원보다 낫다고 직접 비교', 0), (@qid, 1, '자체 의료진의 경력, 보유 장비, 인증 등 객관적 사실을 나열', 1), (@qid, 2, '타 병원의 단점을 부각', 0), (@qid, 3, '\"업계 최고\"라고 표현', 0);

INSERT INTO quizzes (workspace_id, category_risk_code, difficulty, question, explanation, source_ref, is_active) VALUES
(1, 'RISK_GUARANTEE_CERTAINTY', 'normal', '의료광고에서 통계를 인용할 때 주의할 점은?', '출처, 조사기간, 대상을 명시해야 합니다.', '의료광고 심의 가이드라인', 1);
SET @qid = LAST_INSERT_ID();
INSERT INTO quiz_choices (quiz_id, choice_no, choice_text, is_correct) VALUES
(@qid, 0, '큰 숫자만 강조하면 됨', 0), (@qid, 1, '통계의 출처, 조사기간, 조사대상을 명시', 1), (@qid, 2, '유리한 수치만 선별적으로 사용', 0), (@qid, 3, '자체 통계도 공식 통계처럼 표기', 0);

SELECT CONCAT('추가 완료! 총 문제 수: ', COUNT(*)) AS message FROM quizzes WHERE workspace_id = 1;
