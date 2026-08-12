@echo off
:: Auto-elevate to Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    powershell -Command "Start-Process '%~f0' -Verb RunAs"
    exit /b
)

echo ============================================================
echo   Menambahkan e-surat.local ke Windows Hosts File
echo ============================================================
echo.

powershell -Command "Add-Content -Path C:\Windows\System32\drivers\etc\hosts -Value '127.0.0.1       e-surat.local' -Force"
ipconfig /flushdns

echo.
echo ============================================================
echo BERHASIL! Domain e-surat.local telah terdaftar di Windows.
echo ============================================================
echo.
echo Langkah terakhir:
echo 1. Buka XAMPP Control Panel ➔ Restart Apache (Stop lalu Start).
echo 2. Buka browser ke: http://e-surat.local/admin
echo.
pause
