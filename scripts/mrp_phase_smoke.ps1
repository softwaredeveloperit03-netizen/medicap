# MRP Phases 1-9 smoke via live API (plant 1126).
# Usage:
#   powershell -File scripts/mrp_phase_smoke.ps1 -Token "<token>"
param(
  [Parameter(Mandatory = $true)][string]$Token,
  [string]$ApiBase = "https://aurenyxgmp.com/php/phpdevlop/phpmedicap",
  [string]$PlantId = "1126",
  [string]$WorkorderNo = "BO002",
  [string]$MaterialCode = "RM0129"
)

$ErrorActionPreference = "Stop"
$out = Join-Path $env:TEMP ("mrp_smoke_" + [guid]::NewGuid().ToString("N") + ".json")
$code = & curl.exe -sS -m 120 -G -w "%{http_code}" -o $out `
  --data-urlencode "type=runMrpPhaseSmoke" `
  --data-urlencode "token=$Token" `
  --data-urlencode "plant_id=$PlantId" `
  --data-urlencode "workorder_no=$WorkorderNo" `
  --data-urlencode "material_code=$MaterialCode" `
  "$ApiBase/mrp/mrp_phase_smoke.php"

$body = [System.IO.File]::ReadAllText($out)
Write-Host "HTTP=$code"
Write-Host $body
$obj = $body | ConvertFrom-Json
if ($obj.status -ne "success") {
  Write-Host "SMOKE_FAILED passed=$($obj.passed) failed=$($obj.failed)"
  exit 1
}
Write-Host "SMOKE_OK passed=$($obj.passed)/$($obj.total)"
exit 0
