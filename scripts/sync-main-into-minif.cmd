@echo off
cd /d "%~dp0.."
git checkout main
git pull origin main
git checkout minif
git merge main
echo Done. You are on minif with latest main merged.
pause
