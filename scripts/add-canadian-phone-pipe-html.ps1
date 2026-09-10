Set-Location "$PSScriptRoot\..\src\app"
$fields = @(
  'contact_number', 'c_mobile_no', 'mobile_no', 'mobile_no1', 'mobile_no2',
  'contact_no', 'phone_no', 'mobNo', 'driver_contact', 'driver_mobile',
  'telephone_no', 'permanent_mobile_no', 'permanent_telephone', 'temp_mobile',
  'temp_telephone', 'emp_contact', 'cr_mobile_no', 'phone', 'mobile',
  'contactNumber', 'phoneNumber', 'whatsappNO', 'cPersonMob'
)
$updated = 0

Get-ChildItem -Recurse -Filter "*.html" | ForEach-Object {
  $content = Get-Content $_.FullName -Raw
  $original = $content
  foreach ($field in $fields) {
    if ($content -notmatch $field) { continue }
    # {{ expr['field'] || ... }}
    $content = [regex]::Replace(
      $content,
      "(?<!\| )(\{\{\s*)([^}|]+?)(\[\s*'$field'\s*\])(\s*(\|\|[^}]+)?\s*\}\})",
      '${1}($2$3 | canadianPhone)$4'
    )
    $content = [regex]::Replace(
      $content,
      "(?<!\| )(\{\{\s*)([^}|]+?)(\[\s*`"$field`"\s*\])(\s*(\|\|[^}]+)?\s*\}\})",
      '${1}($2$3 | canadianPhone)$4'
    )
    # {{ expr.field }}
    $content = [regex]::Replace(
      $content,
      "(?<!\| )(\{\{\s*)([^}|.]+?)(\.${field}\s*)(\}\})",
      '${1}($2$3 | canadianPhone)$4'
    )
    $content = [regex]::Replace(
      $content,
      "(?<!\| )(\{\{\s*)([^}|.]+?)(\.${field}\s*)(\|\|[^}]+)(\}\})",
      '${1}($2$3 | canadianPhone)$4$5'
    )
  }
  if ($content -ne $original) {
    Set-Content -Path $_.FullName -Value $content -NoNewline
    $script:updated++
  }
}
Write-Output "Updated $updated HTML files"
