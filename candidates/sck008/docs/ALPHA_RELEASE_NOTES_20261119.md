# BUDAYAGO Living Heritage PWA v0.2 — ALPHA checkpoint

**Basis:** SCK-007 Security Successor review ZIP (18 Nov 2026). Independent successor copy; original ZIP untouched.

## Implemented (source, not browser-verified)
- `public/heritage.php`: published gallery, collection detail, curator/holder/admin contributor dashboard, authenticated new collection, private multimedia upload, consent workflow, admin publication, withdrawal, moderated narrative contributions.
- `database/20261119_heritage_additive.sql`: THREE additive tables; does NOT drop/alter baseline tables.
- `public/manifest.webmanifest`, `sw.js`, icons (192, 512), new aesthetic shell stylesheet and JS.
- IndexedDB local metadata draft store; **files not available offline**, no automatic upload or background synchronization. Browser capabilities require genuine device tests.
- Existing SCK-007 knowledge, lessons, authentication and security functions preserved. `public/index.php` has only an added navigation link.

## Critical limitations / open gates
1. **Not ready for public real cultural data or production.** Evidence refs are self-reported metadata. A qualified person must examine real informed consent, lawful authority and license for EVERY medium, including voice/face/manuscript. Current approval scopes are collection-level; per-media granular rights and reversible durable proof required before real deployment.
2. Upload is file-type/size screened using PHP `finfo` and 12 MiB limit, but no antivirus, transcoding, media sanitizer, quotas, or file chunking. Videos bigger than limit unsupported. PHP upload_max_filesize and post_max_size must support agreed limit. Very large payloads are blocked by server configuration/WAF.
3. Withdrawal gates access through PHP and no-store headers, but CDN caches / third-party sharing, derivatives, offline previously downloaded media and other public copies are **not remotely revocable**. Ensure media URLs bypass CDN cache; perform compliance review.
4. CSRF/RBAC inherited from SCK-007. New endpoint still requires MySQL runtime integration, genuine UAT, penetration review, concurrency/load tests. IndexedDB data is plain client-side metadata: avoid identifiers and sensitive data in drafts; full device data protection remains future work.
5. Service worker caches only static shell CSS/JS/icons; **no private HTML/API/media cached**, no background sync. PWA installability not verified on mobile or HTTPS staging.
6. New upload flow assumes dedicated private folder under source root, web server docroot strictly `/public`; ensure deny access to any private paths.
7. Public contributions are moderated, not fact-checked by software; only `holder`, `curator`, `admin` can submit currently. Attribution uses account name; obtain permission to show it.
8. Older SCK-007 release gates UAT-25/26/28, independent security and VPS staging remain open.

## Scope status
- SOURCE_IMPLEMENTED: successor PHP/UI/PWA shell/offline metadata and additive SQL.
- LOCALLY_TESTED: PHP syntax, existing unit and throttle/private-ref/DB guard script tests.
- NOT_TESTED: MariaDB migration, authenticated API/HTTP flows, browser PWA, real uploads, real consent, withdrawal with external cache, VPS, real user UAT.

## Product design
Four service groups: Jelajah Budaya, Rekam Budaya, Kontribusi dan Kurasi, Administrasi. Capture one heritage collection as central entity; lesson/quiz remains secondary. Color palette #20473C / #F7F3E9 / #D8AE6F / #A65E43. No fake cultural data or unlicensed imagery shipped. Manuscripts supported as images/PDF files; OCR/transcription/video compression not implemented.

## Track B proposal/RAB sync
Propose a phased pilot: 1) consent and cultural-authority validation, 2) field capture plus media security, 3) controlled staging and accessibility/UAT, 4) real operational pilot with local cultural institutions. Quantified budgets must be based on actual personnel/rates, storage, bandwidth, curation and conservation costs; no estimated fabricated costs are asserted.
