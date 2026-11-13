# SCK-MVP-001 — Build and UAT Checkpoint (2026-11-13)

**Source-of-truth status:** CANDIDATE_BUILD; NOT_DEPLOYED; MYSQL_RUNTIME_NOT_TESTED; UAT_PENDING.

The functional PHP+MySQL MVP source archive is available as a conversation attachment:
`BUDAYAGO_SULBAR_CULTURE_KNOWLEDGE_MVP_v0.1_20261113.zip`

Archive SHA-256: `c846f277bb32679361f233653621c2bcac9c1f58703f8330b29303b8076cbbce`.

**Important:** This branch is a metadata/checkpoint placeholder ONLY. The application source ZIP is NOT committed in this branch, and no live URL is claimed. Extract and verify ZIP on `D:\PROJEK\BUDAYAGO\incoming\SCK_MVP_20261113`, then commit reviewed individual source files on this development branch. Do not overwrite existing workspace files. Do not merge into main until automated and human gates pass.

## Included in source archive
- PHP 8.2+ web application with role-based access `admin / curator / holder / teacher / student`.
- MySQL schema, CLI-only admin provisioning, login/logout, user management and password changes.
- Knowledge lifecycle `draft -> submitted -> holder approved/rejected -> admin published`; holder revocation blocks dependent lessons.
- Per-purpose consent: learning / online / future AI / promotion. AI service **not implemented**.
- Lesson authoring/publication, learner short-answer quiz, summary counters, audit log.
- CSRF, PDO parameter binding, HTML escaping, session protections; README, security limitations and UAT matrix.
- GitHub Actions workflow to test MySQL 8 & HTTP routes after files are committed as source.

## Verified here
- PHP syntax: all 8 PHP files PASS, PHP CLI 8.4.
- Unit: **19 / 19 PASS**.
- ZIP integrity: PASS, 19 entries, SHA256 above.
- MySQL integration, HTTP with real DB, target host, and user acceptance: **NOT EXECUTED** in accessible environment.

## Go-live blockers
1. Obtain verified MySQL 8/PHP 8.2+ environment and run `tests/integration.php`, `tests/http_smoke.sh`.
2. Pass 35 acceptance scenarios in `docs/UAT_MATRIX.md` with test evidence.
3. Validate accounts, TLS, backups, restrictive public web document root, log monitoring, login abuse throttling and data rights.
4. Verify local folder contents and reviewed source; preserve the initial remote LICENSE until owner verifies attribution.
5. Obtain real verified cultural authority approval before real material is published.
6. Owner approves staging and go-live after evidence review.

This November 2026 build **does not prove the product existed on 31 March 2026** for BUDAYA GO! 2026 eligibility.
