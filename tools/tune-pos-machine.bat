@echo off
REM ─────────────────────────────────────────────────────────────────────
REM  ปรับตั้งค่าเครื่อง POS ให้เร็วขึ้น
REM  คลิกขวาที่ไฟล์นี้ -> Run as administrator
REM ─────────────────────────────────────────────────────────────────────
chcp 65001 >nul

net session >nul 2>&1
if errorlevel 1 (
  echo.
  echo   Need Administrator rights - requesting elevation...
  powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
  exit /b
)

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0tune-pos-machine.ps1" %*

echo.
pause
