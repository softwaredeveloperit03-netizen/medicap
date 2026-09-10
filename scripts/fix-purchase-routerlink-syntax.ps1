$root = 'e:\Medicap New\frontend\src\app\purchase'
$bad = '[routerLink="[''/purchase'']" [queryParams]'
$good = '[routerLink]="[''/purchase'']" [queryParams]'
Get-ChildItem -Path $root -Recurse -Filter '*.html' | ForEach-Object {
  $c = Get-Content -Raw -LiteralPath $_.FullName
  if ($c.Contains($bad)) {
    Set-Content -LiteralPath $_.FullName -Value ($c.Replace($bad, $good)) -NoNewline
    Write-Host "Fixed: $($_.FullName)"
  }
}
Write-Host 'Done.'
