$root = Join-Path $PSScriptRoot '..\src\app\purchase'

function Update-File($path, [scriptblock]$transform) {
  if (-not (Test-Path -LiteralPath $path)) { return }
  $content = Get-Content -Raw -LiteralPath $path
  $newContent = & $transform $content
  if ($newContent -ne $content) {
    Set-Content -LiteralPath $path -Value $newContent -NoNewline
    Write-Host "Updated: $path"
  }
}

$reqClose = "[routerLink=""['/purchase']"" [queryParams]=""{ category: 'Purchase Requisition' }"""
$orderClose = "[routerLink=""['/purchase']"" [queryParams]=""{ category: 'Purchase' }"""
$vendorClose = "[routerLink=""['/purchase']"" [queryParams]=""{ category: 'Vendor Registration' }"""
$quotClose = "[routerLink=""['/purchase']"" [queryParams]=""{ category: 'Quotation' }"""
$stockClose = "[routerLink=""['/purchase']"" [queryParams]=""{ category: 'Stock' }"""
$postClose = "[routerLink=""['/purchase']"" [queryParams]=""{ category: 'Post Receiving Status' }"""
$reportClose = "[routerLink=""['/purchase']"" [queryParams]=""{ category: 'Purchase Report' }"""
$plainPurchaseLink = "[routerLink=""['/purchase']"""

Get-ChildItem -Path (Join-Path $root 'indend') -Recurse -Filter '*.html' | ForEach-Object {
  Update-File $_.FullName {
    param($c)
    $c = $c.Replace('routerLink="/purchase/indend/"', $reqClose)
    $c = $c.Replace('routerLink="/purchase/indend"', $reqClose)
    return $c
  }
}

Get-ChildItem -Path (Join-Path $root 'order') -Recurse -Filter '*.html' | ForEach-Object {
  Update-File $_.FullName {
    param($c)
    $c = $c.Replace('routerLink="/purchase/order/raw/"', $orderClose)
    $c = $c.Replace('routerLink="/purchase/order/raw"', $orderClose)
    return $c
  }
}

Get-ChildItem -Path (Join-Path $root 'vendor') -Recurse -Filter '*.html' | ForEach-Object {
  Update-File $_.FullName {
    param($c)
    $c = $c.Replace('routerLink="/purchase/vendor/material"', $vendorClose)
    $c = $c.Replace('routerLink="/purchase/vendor"', $vendorClose)
    return $c
  }
}

Get-ChildItem -Path (Join-Path $root 'quotation') -Recurse -Filter '*.html' | ForEach-Object {
  Update-File $_.FullName {
    param($c)
    $c = $c.Replace('routerLink="/purchase/quotation/log"', $quotClose)
    $c = $c.Replace('routerLink="/purchase/quotation"', $quotClose)
    return $c
  }
}

@(
  'stock\dashboard\dashboard.component.html',
  'stock\raw\dashboard\dashboard.component.html',
  'stock\packing\dashboard\dashboard.component.html',
  'stock\raw\test\test.component.html',
  'stock\raw\quarantine\quarantine.component.html',
  'stock\raw\approved\approved.component.html',
  'stock\packing\test\test.component.html',
  'stock\packing\quarantine\quarantine.component.html',
  'stock\packing\approved\approved.component.html'
) | ForEach-Object {
  Update-File (Join-Path $root $_) {
    param($c)
    $c = $c.Replace($plainPurchaseLink, $stockClose)
    $c = $c.Replace('routerLink="/purchase/stock/raw"', $stockClose)
    $c = $c.Replace('routerLink="/purchase/stock/packing"', $stockClose)
    $c = $c.Replace('routerLink="/purchase/stock"', $stockClose)
    return $c
  }
}

Update-File (Join-Path $root 'indend\dashboard\dashboard.component.html') { param($c) $c.Replace($plainPurchaseLink, $reqClose) }
Update-File (Join-Path $root 'order\raw\dashboard\dashboard.component.html') { param($c) $c.Replace($plainPurchaseLink, $orderClose) }
Update-File (Join-Path $root 'quotation\dashboard\dashboard.component.html') { param($c) $c.Replace($plainPurchaseLink, $quotClose) }
Update-File (Join-Path $root 'vendor\dashboard\dashboard.component.html') { param($c) $c.Replace($plainPurchaseLink, $vendorClose) }
Update-File (Join-Path $root 'vendor\material\dashboard\dashboard.component.html') { param($c) $c.Replace($plainPurchaseLink, $vendorClose) }
Update-File (Join-Path $root 'vendor\document\dashboard\dashboard.component.html') { param($c) $c.Replace($plainPurchaseLink, $vendorClose) }

Update-File (Join-Path $root 'reports\dashboard\dashboard.component.html') { param($c) $c.Replace('routerLink="/purchase"', $reportClose) }
Update-File (Join-Path $root 'post-receiving-status\dashboard\dashboard.component.html') {
  param($c)
  $c = $c.Replace('routerLink="/purchase/"', $postClose)
  return $c.Replace('routerLink="/purchase"', $postClose)
}
Update-File (Join-Path $root 'grn-status\dashboard\dashboard.component.html') { param($c) $c.Replace($plainPurchaseLink, $postClose) }
Update-File (Join-Path $root 'expman\expman.component.html') { param($c) $c.Replace('routerLink="/purchase"', $quotClose) }
Update-File (Join-Path $root 'raw\dashboard\dashboard.component.html') { param($c) $c.Replace('routerLink="/purchase"', $reqClose) }
Update-File (Join-Path $root 'quotation\log\log.component.html') { param($c) $c.Replace('routerLink="/purchase"', $quotClose) }
Update-File (Join-Path $root 'quotation\report\report.component.html') { param($c) $c.Replace('routerLink="/purchase"', $quotClose) }

Write-Host 'Done.'
