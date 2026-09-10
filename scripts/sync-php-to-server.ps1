# Upload Medicap PHP backend to aurenyxgmp.com (manual FTP/cPanel steps)
# Run from project root: .\scripts\sync-php-to-server.ps1

$source = Join-Path $PSScriptRoot "..\php\phpdevlop\phpmedicap"
$remotePath = "/public_html/php/phpdevlop/phpmedicap/"

Write-Host "Medicap PHP — upload these to server: $remotePath" -ForegroundColor Cyan
Write-Host ""

$required = @(
  "checkLogin.php",
  "login.php",
  "login_client.php",
  "health.php",
  "db.php",
  "db1.php",
  "db.config.php",
  "token.php",
  "shared\auth_helper.php",
  "shared\login_security_helper.php"
)

foreach ($f in $required) {
  $full = Join-Path $source $f
  if (Test-Path $full) {
    Write-Host "  [OK] $f"
  } else {
    Write-Host "  [MISSING] $f" -ForegroundColor Red
  }
}

Write-Host ""
Write-Host "After upload, verify:" -ForegroundColor Yellow
Write-Host "  https://aurenyxgmp.com/php/phpdevlop/phpmedicap/health.php?plant_id=1126"
Write-Host ""
Write-Host "Frontend (localhost:7676) uses server API by default." -ForegroundColor Green
Write-Host "To use local XAMPP instead: localStorage.setItem('api_mode','local') then refresh."
