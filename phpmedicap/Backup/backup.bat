@echo off
:: Set database credentials
set DB_HOST=162.214.204.236
set DB_USER=cpplgmp_admin
set DB_PASSWORD=Cppl@1979
set DB_NAME=cpplgmp_nootan
set BACKUP_PATH=C:\Users\Netizens\Desktop\Backup

:: Create backup folder if not exists
if not exist "%BACKUP_PATH%" mkdir "%BACKUP_PATH%"

:: Get current date and time for filename
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set datetime=%datetime:~0,4%-%datetime:~4,2%-%datetime:~6,2%_%datetime:~8,2%-%datetime:~10,2%-%datetime:~12,2%

:: Backup command for remote MySQL server
set BACKUP_FILE=%BACKUP_PATH%\backup_%datetime%.sql
"C:\xampp\mysql\bin\mysqldump.exe" -h %DB_HOST% -u %DB_USER% -p%DB_PASSWORD% %DB_NAME% > "%BACKUP_FILE%"

:: Check if backup was successful
if %errorlevel%==0 (
    echo Backup successful: %BACKUP_FILE%
) else (
    echo Backup failed!
)

pause