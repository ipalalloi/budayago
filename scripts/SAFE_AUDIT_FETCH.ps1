# BUDAYA GO! read-only-safe audit synchronization.
# Execute locally in Windows PowerShell; does NOT pull, reset, switch, merge, or overwrite files.
$ErrorActionPreference = "Stop"
$Project = 'D:\PROJEK\BUDAYAGO'
$ExpectedRepo = 'ipalalloi/budayago'
$ReviewRef = 'review/budayago-eligibility-20261113'

if (-not (Get-Command git -ErrorAction SilentlyContinue)) { throw 'git.exe not found in PATH' }
if (-not (Test-Path -LiteralPath $Project -PathType Container)) {
  throw "Project folder missing: $Project. Verify the actual workspace path first."
}
Set-Location -LiteralPath $Project
if (-not (Test-Path -LiteralPath (Join-Path $Project '.git'))) {
  Write-Warning "Local directory exists but is NOT a Git working tree. Do NOT git clone over this folder. Inventory and back up its existing files, then decide how to attach the remote."
  exit 2
}
$origin = (& git remote get-url origin 2>$null | Out-String).Trim()
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($origin)) { throw 'No origin configured: inspect manually before proceeding' }
$normalized = ($origin -replace '\\.git$','' -replace '^git@github.com:', 'https://github.com/' -replace '^ssh://git@github.com/', 'https://github.com/')
if ($normalized -notmatch '^(https://github\.com/ipalalloi/budayago)$') {
  throw "Remote origin mismatch: $origin. No changes made."
}
Write-Host '=== Local status BEFORE remote read ==='
& git status -sb
& git log -1 --date=iso-strict --format='%H %ad %s'
Write-Host '=== Fetch audit branch (updates only remote refs) ==='
& git fetch origin $ReviewRef
if ($LASTEXITCODE -ne 0) { throw 'Fetch failed' }
Write-Host '=== Audit branch head ==='
& git log -1 --format='%H %ad %s' 'origin/review/budayago-eligibility-20261113'
Write-Host '=== Preview files changed vs main ==='
& git diff --stat 'origin/main...origin/review/budayago-eligibility-20261113'
Write-Host '=== Local uncommitted files (do not discard) ==='
& git status --porcelain=v1
Write-Host '=== DONE: Remote branch fetched. Files NOT merged into your local working tree. ==='
Write-Host 'Review on GitHub and reconcile all local work before intentionally merging.'
