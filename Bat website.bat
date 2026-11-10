@echo off
chcp 65001 >nul
setlocal
title Sunny Grove - bat website
rem Bam dup de bat website chay thu (WordPress Playground) tai http://127.0.0.1:9500
rem File nay tu tim thu muc cua chinh no, nen chuyen thu muc du an di dau cung chay duoc.
cd /d "%~dp0"
set "URL=http://127.0.0.1:9500/"

rem 1. Server dang chay roi -> chi mo trinh duyet.
netstat -ano | findstr /r /c:":9500 .*LISTENING" >nul
if not errorlevel 1 (
	echo Website dang chay san. Dang mo trinh duyet...
	start "" "%URL%"
	timeout /t 2 >nul
	exit /b 0
)

rem 2. Kiem tra cong cu can thiet.
where node >nul 2>nul
if errorlevel 1 (
	echo Chưa cài Node.js. Tải tại https://nodejs.org rồi bấm đúp lại file này.
	pause
	exit /b 1
)
if not exist "start-server.ps1" (
	echo Không thấy file start-server.ps1 trong thư mục:
	echo %CD%
	pause
	exit /b 1
)
if not exist ".dev\node_modules\.bin\wp-playground-cli.cmd" (
	echo Lần đầu chạy: đang cài công cụ chạy thử, vui lòng chờ...
	pushd .dev
	call npm install
	popd
)

rem 3. Bat server trong cua so rieng (dong cua so do = tat website).
echo Đang bật website Sunny Grove... (cửa sổ "Sunny Grove server" mở ra, đừng đóng nó khi còn dùng)
start "Sunny Grove server" /D "%~dp0." powershell -NoProfile -NoExit -ExecutionPolicy Bypass -File "%~dp0start-server.ps1"

rem 4. Cho server san sang (toi da khoang 3 phut) roi mo trinh duyet.
set /a N=0
:wait
timeout /t 2 >nul
curl -s -o nul "%URL%" >nul 2>nul
if not errorlevel 1 goto ready
set /a N+=1
if %N% lss 90 goto wait
echo Website chưa lên sau 3 phút. Xem lỗi trong cửa sổ "Sunny Grove server".
pause
exit /b 1

:ready
echo Website đã sẵn sàng: %URL%
start "" "%URL%"
timeout /t 3 >nul
exit /b 0
