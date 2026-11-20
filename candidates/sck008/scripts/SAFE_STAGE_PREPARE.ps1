param(
 [Parameter(Mandatory=$true)][string]$Workspace,
 [Parameter(Mandatory=$true)][string]$Archive,
 [string]$DestinationName='LIVING_HERITAGE_ALPHA_STAGING'
)
$ErrorActionPreference='Stop'
if (-not (Test-Path -LiteralPath $Workspace -PathType Container)) {throw "Workspace folder does not exist"}
if (-not (Test-Path -LiteralPath $Archive -PathType Leaf)) {throw "ZIP not found"}
if ($DestinationName -match '[\\/:*?"<>|]' -or $DestinationName -eq '.' -or $DestinationName -eq '..') {throw "Invalid destination name"}
$dest=Join-Path $Workspace $DestinationName
if(Test-Path -LiteralPath $dest){throw "Destination already exists; will not overwrite: $dest"}
$hash=(Get-FileHash -LiteralPath $Archive -Algorithm SHA256).Hash
Write-Host "SOURCE_SHA256=$hash"
New-Item -ItemType Directory -Path $dest -ErrorAction Stop | Out-Null
try {Expand-Archive -LiteralPath $Archive -DestinationPath $dest -ErrorAction Stop}
catch {Remove-Item -LiteralPath $dest -Force -Recurse; throw}
Write-Host "Extracted safely to: $dest"
Write-Host 'No database migration, server deployment, Git operations, or overwrite performed.'
Write-Host 'Review README_LIVING_HERITAGE.md and docs/STAGING_INSTALL_AND_ROLLBACK.md before staging.'
