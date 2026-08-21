@echo off
title POS Cash Drawer / Print Agent
cd /d "%~dp0"

echo ============================================
echo    POS Cash Drawer / Print Agent
echo ============================================
echo.

set "NODE_EXE="

rem 1) portable node placed in this folder (.\node\node.exe) - copy anywhere and run
if exist "%~dp0node\node.exe" set "NODE_EXE=%~dp0node\node.exe"

rem 2) node installed and available on PATH
if not defined NODE_EXE (
  where node >nul 2>&1 && set "NODE_EXE=node"
)

rem 3) common install locations (node installed but not on PATH)
if not defined NODE_EXE if exist "%ProgramFiles%\nodejs\node.exe" set "NODE_EXE=%ProgramFiles%\nodejs\node.exe"
if not defined NODE_EXE if exist "%ProgramFiles(x86)%\nodejs\node.exe" set "NODE_EXE=%ProgramFiles(x86)%\nodejs\node.exe"
if not defined NODE_EXE if exist "%LOCALAPPDATA%\Programs\nodejs\node.exe" set "NODE_EXE=%LOCALAPPDATA%\Programs\nodejs\node.exe"

if defined NODE_EXE goto run

echo [!] Node.js not found on this PC.
echo.

rem If a prebuilt portable EXE exists, launch it (no Node needed)
if exist "%~dp0dist\win-unpacked\POS Cash Drawer.exe" (
  echo Found portable app (no Node required) - launching...
  start "" "%~dp0dist\win-unpacked\POS Cash Drawer.exe"
  exit /b 0
)
if exist "%~dp0dist\POS-CashDrawer-win32-x64\POS-CashDrawer.exe" (
  echo Found portable app (no Node required) - launching...
  start "" "%~dp0dist\POS-CashDrawer-win32-x64\POS-CashDrawer.exe"
  exit /b 0
)

rem Try to auto-install Node.js via winget (Windows 10/11)
where winget >nul 2>&1
if %errorlevel%==0 (
  choice /C YN /M "Install Node.js now via winget"
  if errorlevel 2 goto manual
  winget install -e --id OpenJS.NodeJS.LTS --accept-source-agreements --accept-package-agreements
  echo.
  echo Done. Close this window and run start.bat again.
  pause
  exit /b 0
)

:manual
echo.
echo Please install Node.js (LTS) first, then run start.bat again.
echo Opening the download page...
start "" "https://nodejs.org/en/download"
pause
exit /b 1

:run
echo [OK] Node: %NODE_EXE%
echo Starting agent... (press Ctrl+C to stop)
echo.
"%NODE_EXE%" "%~dp0agent.js"
echo.
echo Agent stopped.
pause
