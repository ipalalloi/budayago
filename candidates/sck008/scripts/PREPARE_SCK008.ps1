param(
  [Parameter(Mandatory=$true)][string]$Archive,
  [Parameter(Mandatory=$true)][ValidatePattern('^[a-fA-F0-9]{64}$')][string]$ExpectedSha256,
  [string]$Workspace='D:\PROJEK\BUDAYAGO'
)
$ErrorActionPreference='Stop'
if (!(Test-Path -LiteralPath $Workspace -PathType Container)) { throw 'Workspace belum tersedia.' }
if ((Get-FileHash -LiteralPath $Archive -Algorithm SHA256).Hash -ne $ExpectedSha256) { throw 'SHA256 ZIP berbeda.' }
$dest=Join-Path $Workspace ('SCK008_'+[guid]::NewGuid().ToString('N'))
Expand-Archive -LiteralPath $Archive -DestinationPath $dest
$root=Join-Path $dest 'BUDAYAGO'
$manifest=Join-Path $root 'SHA256SUMS.txt'
foreach ($entry in Get-Content -LiteralPath $manifest) {
  if ($entry -notmatch '^([a-f0-9]{64})  (.+)$') { throw 'Manifest tidak valid.' }
  $expected=$Matches[1]
  $path=Join-Path $root $Matches[2]
  if ((Get-FileHash -LiteralPath $path -Algorithm SHA256).Hash -ne $expected) { throw "Checksum berbeda: $path" }
}
Write-Host "Paket terverifikasi: $root"
Write-Host 'Buka START_SCK008.md dan ops/CLOUDPANEL.md.'
