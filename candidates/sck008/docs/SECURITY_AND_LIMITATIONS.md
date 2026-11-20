# Security / known limitations — 2026-11-13

## In-code controls (still require runtime proof)
- PDO parameterized SQL; escaping of all user-provided HTML with `htmlspecialchars`.
- Role-gated routes and domain transitions, not UI-only restrictions.
- Community holder is sole approver; administrators publish **after** holder approval.
- Distinct scopes for education, internet publishing, future AI and promotions; withdrawals block public/learning read access and permanently lock that content record against revision; any genuinely new source needs fresh independent rights review.
- CSRF verification for all POST actions, PHP strict sessions, regenerated session IDs on login, HttpOnly/SameSite session cookies and inactivity timeout.
- Content-Security-Policy (scripts disabled), restrictive X-Frame-Options and Content-Type protections.
- User accounts require admin provisioning; students do not self-register and upload is disabled.
- No third-party JS/API tracking in MVP.

## Security limitations to resolve before a broad public launch
- Login rate limiting is currently **per session**, not global or IP-independent. Add application-level shared throttling, fail2ban/WAF rules, and an operational abuse process before broad public exposure. Host-wide rate limiting alone must be evaluated.
- CSRF controls must be rechecked after installation behind TLS termination/reverse proxy.
- Email-based password recovery/MFA not provided; secure admin-controlled resets only.
- School cohort/tenant isolation not implemented. `MEMBERS` means *all authenticated accounts*; do not use as a school-specific boundary.
- Student data retention/erasure and parental/school permission requirements must be defined before inviting real students.
- Admin actions are logged, but no independently tamper-evident external audit trail exists yet.
- No HTML upload, recording ingestion, AI prompt processing, automatic translation, audiovisual content or integration with payment providers exists.
- Test suite is intentionally staged: static/unit tests can run without database, but **integration and HTTP tests require PHP pdo_mysql + MySQL**.
- Deploy only behind HTTPS, configure backups, least-privilege MySQL account, server security headers, offsite monitoring, and an incident-response contact.

## Cultural safety
- Do not place `RESTRICTED` information into the platform if server operators are not authorized to hold it; even database admins technically can access stored records.
- Checkbox approval captures an application workflow event, **not independent evidence** that the actor has customary authority or has supplied a properly informed, documented authorization.
- Human rights/license review is required before enabling public cultural content. Pre-existing cultural knowledge does not become the developer's copyright merely because entered into software.
- Separate licensable software from non-commercial or community-governed cultural content. Current GitHub MIT license cannot automatically be extended to protected cultural data.
- Do not put consent forms, student identities or recordings into a public GitHub repository.

## Recommended reference standards
- OWASP Application Security Verification Standard and Session Management / CSRF Prevention Cheat Sheets.
- PHP manual: password_hash, PDO prepared statements, session.use_strict_mode, session.cookie_secure and cookie_samesite.
- UNESCO Intangible Cultural Heritage ethical principles and local/community access rules.
