@echo off
setlocal

REM AdSafe 법령 모니터링 수동/스케줄 실행용
REM NOTE: Apache/PHP(localhost)가 실행 중이어야 API 호출이 성공합니다.
curl.exe -s -X POST "http://localhost/AdSafe/api/law-monitor/check" >nul 2>&1

exit /b 0
