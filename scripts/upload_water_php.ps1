$ErrorActionPreference = "Stop"

$local = "c:\Madhav\Medicap\phpmedicap\qc\water.php"
$remote = "ftp://ftp.aurenyxgmp.com/qc/water.php"

$req = [System.Net.FtpWebRequest]::Create($remote)
$req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
$req.Credentials = New-Object System.Net.NetworkCredential("Medicap@aurenyxgmp.com", "Prajwal@1979")
$req.UseBinary = $true
$req.UsePassive = $true
$req.KeepAlive = $false

$bytes = [System.IO.File]::ReadAllBytes($local)
$req.ContentLength = $bytes.Length

$stream = $req.GetRequestStream()
$stream.Write($bytes, 0, $bytes.Length)
$stream.Close()

$resp = $req.GetResponse()
Write-Output ("Status: {0}" -f $resp.StatusDescription.Trim())
$resp.Close()

