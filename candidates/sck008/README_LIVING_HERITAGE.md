# BUDAYAGO — Living Heritage PWA v0.2 Alpha source

This is an additive, local **successor candidate** of SCK-007, not a production release. Open `docs/ALPHA_RELEASE_NOTES_20261119.md`, `docs/STAGING_INSTALL_AND_ROLLBACK.md`, `docs/UAT_LIVING_HERITAGE.md`, `docs/BASELINE_REGISTRY.md` before use.

Public entry points: `/heritage.php` (new cultural gallery + capture) and `/index.php` (prior learning functionality). Deploy under HTTPS with **only public/** web-accessible. The SCK-007 package has no `bin/install-admin.php`; obtain the verified provisioning CLI from an approved baseline, not from an unknown archive.

Create staging using `database/mysql.sql` then `database/20261119_heritage_additive.sql`. For existing authorized SCK-007 staging DB, use only additive migration with verified backup. Do not run on protected sck_uat/sck_ci.

**RELEASE STATUS:** code constructed; local PHP syntax and inherited CLI checks executed; database/browser/real upload end-to-end not verified. No fabricated cultural content included.
