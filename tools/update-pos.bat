@echo off
REM ─────────────────────────────────────────────────────────────────────
REM  POS Auto Update - double click to update the shop machine
REM ─────────────────────────────────────────────────────────────────────
chcp 65001 >nul

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0update-pos.ps1" %*

echo.
pause
