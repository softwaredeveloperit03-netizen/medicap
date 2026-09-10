Set-Location "$PSScriptRoot\..\src\app"
$sharedImport = "import { SharedModule } from 'src/app/shared/shared.module';"
$updated = 0
Get-ChildItem -Recurse -Filter "*.module.ts" | ForEach-Object {
  if ($_.Name -eq 'shared.module.ts') { return }
  $content = Get-Content $_.FullName -Raw
  if ($content -match 'SharedModule') { return }
  if ($content -notmatch 'FormsModule|ReactiveFormsModule') { return }

  if ($content -notmatch 'import \{ SharedModule \}') {
    if ($content -match "import \{[^}]+\} from '@angular/forms';") {
      $content = $content -replace "(import \{[^}]+\} from '@angular/forms';)", "`$1`r`n$sharedImport"
    } else {
      $content = $content -replace "(import \{ NgModule \} from '@angular/core';)", "`$1`r`n$sharedImport"
    }
  }

  if ($content -match 'imports:\s*\[' -and $content -notmatch 'imports:\s*\[\s*[^\]]*SharedModule') {
    $content = $content -replace '(imports:\s*\[)', "`$1`r`n    SharedModule,"
    Set-Content -Path $_.FullName -Value $content -NoNewline
    $script:updated++
  }
}
Write-Output "Updated $updated module files"
