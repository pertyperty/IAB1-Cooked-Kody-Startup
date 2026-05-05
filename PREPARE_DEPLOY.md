# Deploy & Release Checklist

This is a short checklist to follow before deploying the Kody app to a staging/production environment.

- [ ] Ensure `config` or environment variables are set for DB credentials (do not commit secrets).
- [ ] Run `php scripts/check_php_syntax.php` and address errors.
- [ ] Run `php scripts/run_smoke_checks.php` to ensure the runtime environment is healthy.
- [ ] Backup the database before migrations.
- [ ] Ensure `kody/assets/uploads/` is on persistent storage and not cleared on deploy.
- [ ] Configure HTTPS and set secure cookie flags in production.
- [ ] Configure proper file permissions (uploads writable by web user, code read-only).
- [ ] Run manual QA checklist (see `QA_MANUAL.md`).
- [ ] Tag release in Git and create release notes summarizing changes.
