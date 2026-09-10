# Medicap FTP upload (PowerShell) — mirrors ftp_upload_medicap.py
param(
  [switch]$Backend,
  [switch]$Frontend,
  [switch]$All
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

# Backend lives at <root>\phpmedicap; older checkouts nested it under php\phpdevlop.
$backendLocal = Join-Path $root 'phpmedicap'
if (-not (Test-Path $backendLocal)) {
  $backendLocal = Join-Path $root 'php\phpdevlop\phpmedicap'
}

$targets = @()
if ($All -or (-not $Backend -and -not $Frontend)) {
  $targets = @('backend', 'frontend')
} else {
  if ($Backend) { $targets += 'backend' }
  if ($Frontend) { $targets += 'frontend' }
}

$cfg = @{
  backend = @{
    Host = 'ftp.aurenyxgmp.com'
    User = 'Medicap@aurenyxgmp.com'
    Password = 'Prajwal@1979'
    Local = $backendLocal
    Remote = '/'
  }
  frontend = @{
    Host = 'ftp.aurenyxgmp.com'
    User = 'ftpmed@medicap.aurenyxgmp.com'
    Password = 'Prajwal@1979'
    Local = Join-Path $root 'dist\apidemo'
    Remote = '/'
  }
}

$skipNames = @('.ftpquota', 'logs.txt', 'token.txt', 'sops.zip', '.git', '.DS_Store', 'Thumbs.db')
$skipDirs = @('node_modules', '.git', '__pycache__')

function Should-Skip([System.IO.FileInfo]$file) {
  if ($skipNames -contains $file.Name) { return $true }
  $ext = $file.Extension.ToLowerInvariant()
  if ($ext -eq '.log' -or $ext -eq '.zip.bak') { return $true }
  if ($file.Name.ToLower().EndsWith('.txt') -and $file.Length -gt 5000000) { return $true }
  return $false
}

$script:ensuredDirs = @{}

function Ensure-FtpDir($requestBase, $creds, [string]$remoteDir) {
  if ($script:ensuredDirs.ContainsKey($remoteDir)) { return }
  $parts = $remoteDir.Trim('/').Split('/') | Where-Object { $_ }
  $path = ''
  foreach ($part in $parts) {
    $path = "$path/$part"
    if ($script:ensuredDirs.ContainsKey($path)) { continue }
    try {
      $req = [System.Net.FtpWebRequest]::Create("$requestBase$path")
      $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
      $req.Credentials = $creds
      $req.UsePassive = $true
      $req.UseBinary = $true
      $req.KeepAlive = $false
      $resp = $req.GetResponse()
      $resp.Close()
    } catch {
      # exists or not creatable — continue
    }
    $script:ensuredDirs[$path] = $true
  }
  $script:ensuredDirs[$remoteDir] = $true
}

function Upload-Tree($name, $t) {
  $local = $t.Local
  if (-not (Test-Path $local)) {
    throw "$name : local path missing: $local"
  }
  Write-Host "=== $name FTP upload ==="
  Write-Host "Local:  $local"
  Write-Host "Remote: $($t.User)@$($t.Host)$($t.Remote)"

  $creds = New-Object System.Net.NetworkCredential($t.User, $t.Password)
  $baseUrl = "ftp://$($t.Host)"
  $uploaded = 0
  $skipped = 0

  $files = Get-ChildItem -Path $local -Recurse -File | Where-Object {
    $relDir = $_.DirectoryName.Substring($local.Length).TrimStart('\', '/')
    $parts = if ($relDir) { $relDir.Split('\') } else { @() }
    -not ($parts | Where-Object { $skipDirs -contains $_ })
  }

  foreach ($file in $files) {
    if (Should-Skip $file) {
      $skipped++
      continue
    }
    $rel = $file.FullName.Substring($local.Length).Replace('\', '/').TrimStart('/')
    $remotePath = ($t.Remote.TrimEnd('/') + '/' + $rel).Replace('//', '/')
    $remoteParent = ($remotePath -replace '/[^/]+$', '')
    if ($remoteParent -and $remoteParent -ne '/') {
      Ensure-FtpDir $baseUrl $creds $remoteParent
    }
    try {
      $uri = "$baseUrl$remotePath"
      $req = [System.Net.FtpWebRequest]::Create($uri)
      $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
      $req.Credentials = $creds
      $req.UsePassive = $true
      $req.UseBinary = $true
      $req.KeepAlive = $false
      $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
      $req.ContentLength = $bytes.Length
      $stream = $req.GetRequestStream()
      $stream.Write($bytes, 0, $bytes.Length)
      $stream.Close()
      $resp = $req.GetResponse()
      $resp.Close()
      $uploaded++
      if (($uploaded % 25) -eq 0) {
        Write-Host "  uploaded $uploaded files..."
      }
    } catch {
      Write-Host "FAIL $($file.FullName) -> $remotePath : $($_.Exception.Message)"
      $skipped++
    }
  }

  Write-Host "$name : uploaded=$uploaded, skipped=$skipped"
}

foreach ($name in $targets) {
  Upload-Tree $name.ToUpper() $cfg[$name]
}

Write-Host 'Done.'
