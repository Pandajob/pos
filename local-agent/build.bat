@echo off
chcp 65001 > nul
echo.
echo ╔══════════════════════════════════════════╗
echo ║  Build POS Cash Drawer Agent → .exe      ║
echo ╚══════════════════════════════════════════╝
echo.

cd /d "%~dp0"

where node >nul 2>&1 || (echo [ERROR] ไม่พบ Node.js && pause && exit /b 1)

echo [1/2] ติดตั้ง dependencies...
npm install
if %errorlevel% neq 0 (echo [ERROR] npm install ล้มเหลว && pause && exit /b 1)

echo.
echo [2/2] กำลัง build...
npx electron-packager . "POS-CashDrawer" --platform=win32 --arch=x64 --out=dist --overwrite --ignore="dist|\.git|build\.bat"
if %errorlevel% neq 0 (echo [ERROR] build ล้มเหลว && pause && exit /b 1)

echo.
echo ✅ เสร็จสิ้น!
echo ไฟล์อยู่ที่: dist\POS-CashDrawer-win32-x64\POS-CashDrawer.exe
echo.
start "" "dist\POS-CashDrawer-win32-x64\"
pause
