<?php
// PHP 내장 서버용 라우터: /api/* 요청을 api/index.php로 전달
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (preg_match('#^/api(?:$|/)#', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    require __DIR__ . '/api/index.php';
    return true;
}
return false;
