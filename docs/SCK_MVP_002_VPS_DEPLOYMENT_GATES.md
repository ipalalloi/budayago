# SCK-MVP-002 — VPS Staging-to-Production Deployment Gate
Decision date: 2026-11-13 (Asia/Makassar)
Decision: owner plans to upload final build to VPS for integrated testing before approving public production release.
Status: **TARGET APPROVED IN PRINCIPLE / SERVER NOT YET IDENTIFIED / DEPLOYMENT NOT EXECUTED**.

## Environment separation
1. LOCAL: `D:\PROJEK\BUDAYAGO`; preserve all existing files, audit local git status before import.
2. CI: lint/unit/MySQL integration/HTTP tests with synthetic data; retain pass/fail evidence.
3. VPS STAGING: isolated subdomain (TBD), new dedicated MySQL database/user; HTTPS; document root `.../public`, never project root; no real restricted cultural content and no real student data.
4. VPS PRODUCTION: separate vhost, database, credentials, and backups. Promotion only after named owner GO decision. Production is NOT the testing substitute for staging.

## Preflight / read-only verification
- OS, PHP CLI vs PHP-FPM runtime versions, `pdo_mysql`, MySQL version, Nginx/Apache virtual host and document root.
- Disk and backup capacity; SSL and DNS ownership; firewall rules; PHP-FPM service.
- GitHub branch and release manifest SHA256 match ZIP content.
- Host-level protections (rate limiting, WAF/fail2ban where appropriate), secure cookies, CSP, no secret files accessible via HTTP.
- Do not commit passwords, private SSH keys, API tokens or MySQL dumps.

## Promotion gates and evidence
- G0 Source and identity: compare ZIP hashes, verify ownership/license and original upstream files.
- G1 CI: all syntax/unit and dedicated MySQL integration tests PASS on reviewed source SHA.
- G2 Staging: HTTP smoke and login; check all 5 roles, CSRF, RBAC, approval scopes, revocation and dependent lesson visibility; verify failure cases.
- G3 Security: HTTPS, least-privilege DB, no public `config.php`, brute force throttle, session protections, backup AND restore drill.
- G4 Cultural: independent permission evidence for content, correct attribution, holder authority, scoped access, no unpublished/restricted content.
- G5 UAT: complete all 35 documented scenarios with actual pass/fail, testers, logs, timestamps; critical blocker count zero.
- G6 Owner GO + production backup + rollback plan, deploy matching tested release and smoke test without exposing sample data.
- G7 Post-release monitoring: incidents, audit events, error logs, server resource health and recovery tests.

## Releases
- Staging build example: `sck-mvp-v0.1.0-rc1` (only when actual release artifacts match).
- Record UTC timestamps for CI and server deployments, git SHA, archive SHA256, reviewer and actual DNS.
- Rollback through previous immutable release + verified compatible DB migration. Never assume file rollback undoes database changes.

## Current blockers
- Confirm VPS control access/hosting panel, staging domain, MySQL and PHP runtime.
- Import ZIP source (currently available as ChatGPT artifact) into reviewed Git development branch and execute real MySQL tests.
- No deploy credentials, no established server site, and no live smoke results verified.
- No pre-31-Mar-2026 competition eligibility claim from the new November candidate.

Source archive: `BUDAYAGO_SULBAR_CULTURE_KNOWLEDGE_MVP_v0.1_20261113.zip` SHA256 `c846f277bb32679361f233653621c2bcac9c1f58703f8330b29303b8076cbbce`.
