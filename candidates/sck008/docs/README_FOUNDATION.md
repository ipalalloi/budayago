# Sulbar Culture Knowledge — Functional MVP v0.1

**Project:** BudayaGO / Sulbar Culture Knowledge  
**Build date:** 13 November 2026  
**Current verification status:** CODE BUILT / STATIC + UNIT TESTED; **MySQL integration, live deployment, and independent user acceptance still pending**.  
**Important:** This is a newly produced software candidate and **cannot be represented as a product proven to exist before the 31 March 2026 BUDAYA GO! eligibility cutoff**.

## Features implemented in source code

- Admin-only account provisioning, active/inactive status, admin password resets, personal password changes and session login/logout.
- Five roles: admin, curator, holder (recognized community knowledge-holder), teacher and student.
- Draft -> submitted -> holder approved/rejected -> admin published lifecycle.
- Per-use permission controls for learning, public internet exposure, future AI use, and promotional use.
- Restricted knowledge records cannot be published; anonymous visitors see only approved public information.
- Knowledge-holder revocation hides the source and all dependent lessons immediately at the authorization layer.
- Lesson creation, publication, learning view, short-answer check, learner-specific summary counts.
- Event audit and admin dashboard counts; escaping, prepared statements, CSRF, no public uploads, secure-cookie defaults.
- Responsive UI in Bahasa Indonesia.

**Intentionally not implemented:** external AI integration, content ingestion/recording uploads, automatic validation of historical/cultural facts, commercial payments, school/tenant isolation, offline/PWA, institutional analytics, email recovery, moderated public self-registration. None of these can be claimed working.

## Deploy prerequisites

- Apache/nginx PHP hosting; **PHP 8.2 or newer**, `PDO` and `pdo_mysql` extensions.
- MySQL 8+ or a compatible MariaDB release (CI uses MySQL 8).
- HTTPS certificate and configured virtual host whose **document root points only to `public/`**.
- Database credentials scoped to a dedicated new database.
- SSH or another **CLI PHP** method to create the initial administrator securely.
- No unapproved cultural content and no real student data until appropriate consultation/consents.

## Deployment steps (new environment only)

1. Download/extract release into your project workspace; **do not overwrite any pre-existing files**. Make an inventory and backup first.
2. Create a NEW MySQL database and account, import `database/mysql.sql` to the empty database. Verify all five tables exist.
3. Copy `config.example.php` to **`config.php` in the project root (NOT in `public/`)** and set DSN, DB username/password, actual HTTPS origin, and `FORCE_HTTPS=true`.
4. Configure a dedicated domain/subdomain with its document root ending in `.../SCK_MVP_20261113/public`.
5. Provision the first admin with CLI PHP, using an actual private email address and a unique 12+ character password:

   ```bash
   read -rsp 'Initial admin password: ' ADMIN_PASSWORD; echo
   export ADMIN_PASSWORD
   ADMIN_EMAIL='owner@example.org' php bin/install-admin.php
   unset ADMIN_PASSWORD
   ```

   **Do not use** `owner@example.org` or the CI/test credentials as actual production credentials. On systems without shell `read`, pass the password through a secure secret manager or temporary environment setting, not source files.
6. Confirm `https://your-domain.example/index.php?p=home` and login. Create separate curator, holder, teacher and student test accounts with non-real identities, then complete the real user-acceptance matrix in `docs/UAT_MATRIX.md`.
7. Test permission withdrawal after lesson publication, unauthorized access, HTTPS redirection, backup and restore. Confirm public content has explicit rights.
8. Only then approve deployment for an authorized pilot. No claim of production readiness before this verification.

## Testing

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/unit.php
```

Database tests against an **isolated disposable** database `sck_ci`:

```bash
mysql -u root -p sck_ci < database/mysql.sql
SCK_ALLOW_TEST_DB=yes DB_DSN='mysql:host=127.0.0.1;port=3306;dbname=sck_ci;charset=utf8mb4' \
DB_USER='root' DB_PASS='YOUR_TEST_PASSWORD' php tests/integration.php
```

CI workflow under `.github/workflows/ci.yml` runs lint, unit, MySQL 8 integration and HTTP smoke tests. **Workflow passing is required but not sufficient** for live acceptance.

## Folder organization

- `public/` — only web-accessible document root.
- `app/` — private business logic, authorization and configuration loader.
- `database/` — fresh database schema, not a migration over existing production databases.
- `bin/` — CLI-only initial account provisioning.
- `tests/` — unit, database and HTTP checks.
- `docs/` — acceptance, release gates, cultural rights and security notes.

## Data policy

Never commit or publicly upload KTPs, children's data, interview recordings, sacred/restricted information, credentials, consent forms, or named private informant records. Approval flags must be supported by independently retained consent evidence in **restricted storage**, not by mere checkbox operation. The system's role `holder` must be assigned only after external verification of cultural authority.

## Repository and license note

Upstream repository https://github.com/ipalalloi/budayago originally had a public MIT LICENSE with copyright attributed to `IzandBox`. This code package **does not amend or warrant that upstream license attribution**. Review code ownership, dataset rights, image rights, and culture permissions before merging or republishing. Software source rights must be evaluated separately from cultural knowledge rights.
