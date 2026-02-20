@echo off
cd /d "%~dp0.."
git checkout main
git pull origin main
git checkout minif
git merge main
git push
echo Done. minif is synced with main and pushed.
pause
