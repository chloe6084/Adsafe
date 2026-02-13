<?php
declare(strict_types=1);

/**
 * AI 광고문구 생성 API
 * - POST /api/ai/generate       — 새 광고문구 생성
 * - GET  /api/ai/history         — 생성 이력 조회
 * - GET  /api/ai/history/:id     — 생성 상세 조회
 */

/**
 * 시스템 프롬프트 생성: 의료광고 규제 룰을 포함
 */
function build_system_prompt(): string {
    // DB/JSON에서 룰 데이터 로드
    require_once __DIR__ . '/../engine/rules_data.php';
    $rules = adsafe_rules();

    $rulesText = "";
    foreach ($rules as $r) {
        $code = $r['riskCode'] ?? '';
        $level3 = $r['level3'] ?? ($r['level2'] ?? '');
        $explanation = $r['explanation'] ?? '';
        $suggestion = $r['suggestion'] ?? '';
        $keywords = is_array($r['keywords'] ?? null) ? implode(', ', $r['keywords']) : '';

        $rulesText .= "- [{$code}] {$level3}: {$explanation}\n";
        $rulesText .= "  금지 키워드: {$keywords}\n";
        $rulesText .= "  대안: {$suggestion}\n\n";
    }

    return <<<PROMPT
당신은 대한민국 의료법 및 의료광고 심의 가이드라인을 숙지한 '의료광고 전문 카피라이터'입니다.

## 핵심 역할
1. 사용자가 요청하는 주제에 맞는 법적으로 안전한 의료 광고 문구를 제작합니다.
2. 아래 의료광고 규제 룰을 반드시 준수하여 리스크가 없는 문구를 생성합니다.
3. 문구 제작에 필요한 정보가 부족하면 반드시 사용자에게 질문합니다.

## 의료광고 규제 룰 (위반 금지 — AdSafe 룰엔진 기준)
{$rulesText}

## 문구 제작 원칙
1. 과장 금지: 절대적 최상급(최고, 최상, 유일, 완벽) → "우수한", "만족도 높은" 등으로 대체
2. 효과 단정 금지: "완치", "해결" → "도움이 될 수 있습니다", "개선에 기여"
3. 부작용 부정 금지: "부작용 없음" → "부작용이 적은 편", "의사와 상담 후"
4. 공포 조성 금지: "방치하면 위험" → "정기 검진 권유" 등 건전한 안내
5. 외모 비하/이상화 금지: 과도한 미화 표현 제거
6. 비교/비방 금지: 타 기관 비교는 객관적 데이터+근거 필수
7. 이벤트/할인: 기간(시작~종료), 할인율, 적용 대상 반드시 명시
8. 환자 후기: "개인 사례이며 결과는 다를 수 있습니다" 문구 포함
9. 보험: 조건/한도 함께 안내, 단정적 표현 금지

## 정보 부족 시 질문할 항목
- 진료과 / 시술명
- 광고 유형 (이벤트·할인, 시술소개, 치료효과, 기관소개 등)
- 타겟 고객층 (연령대, 성별, 관심사 등)
- 강조 포인트 (기술력, 가격, 경험, 시설 등)
- 이벤트의 경우: 기간, 할인율, 적용 대상
- 광고 매체 (SNS, 검색광고, 디스플레이, 블로그 등)

## 출력 형식 (정보가 충분할 때)
### 광고 문구 제안

[안 1] (문구 유형/톤 설명)
> 실제 광고 문구

[안 2] (문구 유형/톤 설명)
> 실제 광고 문구

[안 3] (문구 유형/톤 설명)
> 실제 광고 문구

### 안전성 체크
각 안이 의료광고 규제를 어떻게 준수하는지 간단히 설명합니다.

### 사용 시 주의사항
광고 집행 전 추가로 확인해야 할 사항을 안내합니다.

## 주의
- 정보가 부족하면 문구를 만들기 전에 먼저 질문하세요.
- 의료법 위반 소지가 있는 표현은 절대 사용하지 마세요.
- 모든 문구에는 "본 광고는 참고용이며 실제 효과는 개인에 따라 다를 수 있습니다" 문구를 권장합니다.
PROMPT;
}

/**
 * OpenAI API 호출
 */
function call_openai(string $systemPrompt, array $messages, string $apiKey, string $model = 'gpt-4o-mini'): array {
    $openaiMessages = [
        ['role' => 'system', 'content' => $systemPrompt],
    ];
    foreach ($messages as $msg) {
        $openaiMessages[] = [
            'role' => $msg['role'] ?? 'user',
            'content' => $msg['content'] ?? '',
        ];
    }

    $payload = json_encode([
        'model' => $model,
        'messages' => $openaiMessages,
        'temperature' => 0.7,
        'max_tokens' => 2000,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new RuntimeException('OpenAI API 연결 오류: ' . $curlError);
    }

    $data = json_decode($response, true);
    if ($httpCode !== 200) {
        $errMsg = $data['error']['message'] ?? ('HTTP ' . $httpCode);
        throw new RuntimeException('OpenAI API 오류: ' . $errMsg);
    }

    return $data;
}

/**
 * POST /api/ai/generate
 */
function handle_ai_generate(): void {
    require_once __DIR__ . '/credits.php';

    try {
        $body = read_json_body();
        $userId = (int)($body['user_id'] ?? 0);
        $adType = trim((string)($body['ad_type'] ?? ''));
        $targetAudience = trim((string)($body['target_audience'] ?? ''));
        $keywords = trim((string)($body['keywords'] ?? ''));
        $additionalInfo = trim((string)($body['additional_info'] ?? ''));
        $userPrompt = trim((string)($body['prompt'] ?? ''));

        if ($userId <= 0) json_response(['error' => 'user_id가 필요합니다.'], 400);
        if ($userPrompt === '') json_response(['error' => '프롬프트를 입력해주세요.'], 400);

        $pdo = get_pdo();

        // 크레딧/한도 확인
        $check = check_feature_available($pdo, $userId, 'ai_generate');
        if (!$check['available']) {
            json_response([
                'error' => $check['reason'],
                'available' => false,
                'plan' => $check['plan'] ?? 'free',
            ], 403);
            return;
        }

        // .env에서 API 키 읽기
        $env = load_env_file(__DIR__ . '/../.env');
        $apiKey = $env['OPENAI_API_KEY'] ?? '';
        $model = $env['OPENAI_MODEL'] ?? 'gpt-4o-mini';

        if ($apiKey === '' || $apiKey === '여기에_OpenAI_API_키_입력') {
            json_response(['error' => 'OpenAI API 키가 설정되지 않았습니다. api/.env 파일을 확인하세요.'], 500);
            return;
        }

        // 사용자 메시지 구성
        $contextParts = [];
        if ($adType !== '') $contextParts[] = "광고 유형: {$adType}";
        if ($targetAudience !== '') $contextParts[] = "타겟 고객: {$targetAudience}";
        if ($keywords !== '') $contextParts[] = "강조 포인트: {$keywords}";
        if ($additionalInfo !== '') $contextParts[] = "추가 정보: {$additionalInfo}";

        $fullPrompt = $userPrompt;
        if (!empty($contextParts)) {
            $fullPrompt = implode("\n", $contextParts) . "\n\n" . $userPrompt;
        }

        // 대화 이력 지원 (선택적)
        $conversationHistory = $body['conversation'] ?? [];
        $messages = [];
        if (is_array($conversationHistory) && !empty($conversationHistory)) {
            foreach ($conversationHistory as $msg) {
                $messages[] = [
                    'role' => $msg['role'] ?? 'user',
                    'content' => $msg['content'] ?? '',
                ];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $fullPrompt];

        // 시스템 프롬프트 생성
        $systemPrompt = build_system_prompt();

        // OpenAI API 호출
        $start = microtime(true);
        $apiResponse = call_openai($systemPrompt, $messages, $apiKey, $model);
        $processingMs = (int)round((microtime(true) - $start) * 1000);

        $aiContent = $apiResponse['choices'][0]['message']['content'] ?? '';
        $usage = $apiResponse['usage'] ?? [];
        $promptTokens = (int)($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int)($usage['completion_tokens'] ?? 0);
        $totalTokens = (int)($usage['total_tokens'] ?? 0);

        // DB 저장
        $stmt = $pdo->prepare(
            "INSERT INTO ai_generations (user_id, workspace_id, ad_type, target_audience, keywords, additional_info, user_prompt, ai_response, model_used, prompt_tokens, completion_tokens, total_tokens, status, processing_ms)
             VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)"
        );
        $stmt->execute([
            $userId, $adType ?: null, $targetAudience ?: null, $keywords ?: null,
            $additionalInfo ?: null, $fullPrompt, $aiContent, $model,
            $promptTokens, $completionTokens, $totalTokens, $processingMs,
        ]);
        $generationId = (int)$pdo->lastInsertId();

        // 크레딧 차감 (일일 카운터 증가)
        $pdo->prepare("UPDATE user_credits SET daily_ai_used = daily_ai_used + 1 WHERE user_id = ?")->execute([$userId]);

        // 거래 이력
        $credits = ensure_user_credits($pdo, $userId);
        $pdo->prepare(
            "INSERT INTO credit_transactions (user_id, type, amount, balance_after, description, feature, reference_id)
             VALUES (?, 'use', -1, ?, 'AI 광고문구 생성', 'ai_generate', ?)"
        )->execute([$userId, (int)$credits['credit_balance'], (string)$generationId]);

        json_response([
            'generationId' => $generationId,
            'response' => $aiContent,
            'model' => $model,
            'tokens' => [
                'prompt' => $promptTokens,
                'completion' => $completionTokens,
                'total' => $totalTokens,
            ],
            'processingMs' => $processingMs,
        ]);
    } catch (Throwable $e) {
        // 실패 시에도 기록
        if (isset($pdo) && isset($userId) && $userId > 0) {
            try {
                $pdo->prepare(
                    "INSERT INTO ai_generations (user_id, workspace_id, user_prompt, status, error_message)
                     VALUES (?, 1, ?, 'failed', ?)"
                )->execute([$userId, $fullPrompt ?? '', $e->getMessage()]);
            } catch (Throwable $ignore) {}
        }

        json_response(['error' => 'AI 생성 실패: ' . $e->getMessage()], 500);
    }
}

/**
 * GET /api/ai/history
 */
function handle_ai_history_list(): void {
    try {
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20;
        $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
        $offset = ($page - 1) * $limit;

        if ($userId <= 0) json_response(['error' => 'user_id가 필요합니다.'], 400);

        $pdo = get_pdo();
        $limitInt = (int)$limit;
        $offsetInt = (int)$offset;
        $stmt = $pdo->prepare(
            "SELECT generation_id, ad_type, target_audience, keywords, user_prompt, 
                    LEFT(ai_response, 200) AS ai_response_preview, model_used, status, processing_ms, created_at
             FROM ai_generations 
             WHERE user_id = ? AND status = 'completed'
             ORDER BY created_at DESC 
             LIMIT {$limitInt} OFFSET {$offsetInt}"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM ai_generations WHERE user_id = ? AND status = 'completed'");
        $stmtC->execute([$userId]);
        $total = (int)$stmtC->fetchColumn();

        json_response(['generations' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    } catch (Throwable $e) {
        json_response(['error' => 'AI 이력 조회 실패', 'message' => $e->getMessage()], 500);
    }
}

/**
 * GET /api/ai/history/:id
 */
function handle_ai_history_detail(int $id): void {
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare("SELECT * FROM ai_generations WHERE generation_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            json_response(['error' => '생성 기록을 찾을 수 없습니다.'], 404);
            return;
        }

        json_response($row);
    } catch (Throwable $e) {
        json_response(['error' => 'AI 이력 상세 조회 실패', 'message' => $e->getMessage()], 500);
    }
}
