# Local workspace sync: D:\PROJEK\BUDAYAGO

Local drive `D:` cannot be modified from the assistant container. The steps below preserve existing local files and should only be run in PowerShell after the package is downloaded and its SHA256 verified.

1. Inspect the existing workspace **first** (`Get-ChildItem -Force`, `git status -sb`, `git remote -v`). Do not run `git reset --hard` or unzip over old code.
2. Extract ZIP into a brand-new staging folder, for example `D:\PROJEK\BUDAYAGO\incoming\SCK_MVP_20261113`.
3. Run PHP syntax checks and unit tests there; use a dedicated disposable MySQL database for integration.
4. Only after all gates pass, use a **new feature branch** in the repository, copy reviewed individual files from staging into that branch, and push for code review. Do not commit credentials or protected culture data.
5. Configure a staging server separately from live production. Upload with the hosting account's secured deployment workflow. Obtain owner approval before production deployment.

Example (run from existing Git repo only, after backup):

```powershell
Set-Location D:\PROJEK\BUDAYAGO
git status -sb
git remote -v
git switch -c development/sck-mvp-20261113
# Copy validated staging source files into the tracked tree only after inspecting conflicts.
git status --short
# Review before committing; keep SQL dump, config.php, and restricted content outside Git.
```

Do not assume a November 2026 release satisfies BUDAYA GO! 2026's 31 March 2026 historical product-existence rule.
