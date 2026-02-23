<?php
declare(strict_types=1);
/**
 * 검수 이력 DB 저장 진단 (PHP)
 * 실행: php scripts/test-inspect-save.php (api 폴더에서)
 *   또는 c:\xampp2\php\php.exe scripts/test-inspect-save.php
 */

require_once __DIR__ . '/../lib/bootstrap.php';

echo "=== 검수 저장 진단 ===\n\n";

try {
    $pdo = get_pdo();

    $stmt = $pdo->query('SELECT workspace_id FROM workspaces WHERE workspace_id = 1');
    if (!$stmt->fetch()) {
        echo "원인: workspace_id=1 이 없습니다.\n";
        echo "해결: php scripts/seed.php 를 실행하세요.\n";
        exit(1);
    }
    echo "OK: workspaces(1) 존재\n";

    $stmt = $pdo->query('SELECT user_id FROM users WHERE user_id = 1');
    if (!$stmt->fetch()) {
        echo "원인: user_id=1 이 없습니다.\n";
        echo "해결: php scripts/seed.php 를 실행하세요.\n";
        exit(1);
    }
    echo "OK: users(1) 존재\n";

    $ins = $pdo->prepare(
        "INSERT INTO inspection_runs (workspace_id, project_id, copy_id, copy_version_no, rule_set_version_id, risk_summary_level, total_findings, normalized_text, processing_ms, created_by)
         VALUES (1, NULL, NULL, NULL, NULL, 'none', 0, '진단 테스트', 10, 1)"
    );
    $ins->execute();
    $runId = $pdo->lastInsertId();
    echo "OK: inspection_runs INSERT 성공, run_id = {$runId}\n";

    $pdo->prepare('DELETE FROM inspection_runs WHERE run_id = ?')->execute([$runId]);
    echo "OK: 테스트 행 삭제 완료\n";

    echo "\n=> DB 저장 가능 상태입니다.\n";
} catch (Throwable $e) {
    echo "저장 실패: " . $e->getMessage() . "\n";
    echo "\n해결: php scripts/seed.php 를 실행한 뒤 다시 시도하세요.\n";
    exit(1);
}
