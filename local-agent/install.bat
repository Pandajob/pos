@echo off
cd /d "%~dp0"
echo ============================================
echo    POS Cash Drawer Agent - optional setup
echo ============================================
echo.
echo NOTE: agent.js runs with Node only - no npm install needed.
echo This script only installs OPTIONAL extras:
echo   - serialport  (only if your drawer/printer uses a COM port)
echo   - electron    (only if you want to build the desktop app)
echo.

where node >nul 2>&1
if %errorlevel% neq 0 (
  echo [ERROR] Node.js not found. Install it from https://nodejs.org then re-run.
  pause
  exit /b 1
)

for /f "tokens=*" %%v in ('node --version') do set NODE_VER=%%v
echo [OK] Node.js %NODE_VER%
echo.

echo Installing dependencies (express + dev/build tools)...
call npm install
echo.

echo Installing serialport (optional - for COM port drawers)...
call npm install serialport --save-optional
echo.

echo ============================================
echo  Done. To run the agent:  start.bat
echo  (or:  node agent.js)
echo  The token is printed on startup - paste it
echo  into POS Settings, or use the auto-pair button.
echo ============================================
pause
