# Kody Development and Deployment Baseline

## Status

Production-ready baseline as of May 2026.

## Purpose

This document records implementation status, deployment expectations, and prioritized follow-up work for the Kody platform.

## Project Snapshot

| Area | Status | Notes |
| ---- | ------ | ----- |
| Authentication and RBAC | Complete | Register, login, logout, role checks active |
| Learning workflows | Complete | Courses, enrollment, submissions, progress active |
| Gamification | Complete | XP and level updates active |
| Subscriptions and payments | Complete | Plan selection and payment recording active |
| Admin CRUD coverage | Complete | Core schema surfaced in admin modules |
| Documentation set | Complete | README plus audit reports updated |
| Dark UI migration | Complete | Global and admin styles modernized |

## Architecture Baseline

| Layer | Technology | Current Baseline |
| ----- | ---------- | ---------------- |
| Frontend | HTML, CSS, JavaScript | Responsive dark UI with shared styles |
| Backend | PHP | Modular includes and helper-based query access |
| Database | MySQL | 19-table schema with relational constraints |
| Admin | PHP generic CRUD | Module-to-table mapping in includes/functions.php |

## Implementation Phases

### Phase 0 Environment and Schema

- Environment setup completed.
- Schema deployment from database.sql completed.
- Optional seed flow available via seed_phase_0_5_rerun_safe.sql.

### Phase 1 Shared Core and Navigation

- includes/db.php, includes/auth.php, includes/functions.php established.
- Shared header and footer integration established.
- Role-aware navigation behavior implemented.

### Phase 2 Authentication and Role Access

- register.php and login.php workflows completed.
- Password hashing and verification implemented.
- Role assignment on registration implemented.
- Session-gated access for admin pages implemented.

### Phase 3 Core User Pages

- dashboard.php, course.php, leaderboard.php integrated with live data.
- Course and content browsing with joins and helper functions active.

### Phase 4 Enrollment and Progress

- enroll.php flow completed.
- progress.php create and update paths completed.
- Submission-related progress updates active.

### Phase 5 Admin CRUD

- Generic CRUD engine connected to module wrappers.
- Broad schema coverage enabled in admin panel.
- Additional modules added for user_subscriptions and moderation_reviews.

### Phase 6 UI Modernization

- Global style system updated to dark theme tokens.
- Admin style layer updated for consistency.
- Button, input, table, and hover states normalized.
- Scroll-position retention behavior added in assets/js/app.js.

### Phase 7 Schema Usage Audit

- Field and table usage reports generated.
- Runtime vs admin-only integration gaps identified.
- Roadmap recommendations documented in audit files.

## Functional Baseline

### User-side workflows

1. Register and login.
2. Browse courses and enroll.
3. Access modules and lessons.
4. Submit challenges.
5. Track progress and XP.
6. Manage subscription and payment path.

### Admin-side workflows

1. Manage users, roles, courses, modules, lessons, and challenges.
2. Manage submissions, enrollment, progress, and XP.
3. Manage subscription plans, user subscriptions, and payments.
4. Access moderation and notifications CRUD modules.

## Security Baseline

- Password hashing is enabled.
- Prepared statements are used in query paths.
- Session-based auth checks are active.
- Role-based access restrictions are active for admin endpoints.

## Database Baseline

### Runtime-strong tables

- users, roles, user_roles
- courses, modules, lessons, challenges
- course_enrollment, user_progress, submissions
- user_xp, subscription_plans, user_subscriptions, payments

### Partial or roadmap-depth tables

- challenge_testcases (not fully wired into evaluation path)
- moderation_reviews (admin CRUD available, limited user flow)
- notifications (admin CRUD available, limited user flow)
- leaderboard (runtime ranking is computed from user_xp)

## Deployment Baseline

### Requirements

- PHP 7.4 or newer
- MySQL 8.0 or newer
- Web server stack such as Apache or Nginx

### Setup sequence

1. Import database.sql.
2. Optionally import seed_phase_0_5_rerun_safe.sql.
3. Configure database credentials in kody/includes/db.php.
4. Run local syntax and smoke checks when PHP CLI is available.

## Validation Baseline

### Checks

- scripts/check_php_syntax.php
- scripts/run_smoke_checks.php

### Current note

If PHP is unavailable in shell, rely on IDE diagnostics and browser runtime verification.

## Known Gaps and Next Priorities

### High

1. Integrate challenge_testcases into submission validation logic.
2. Decide computed vs persisted leaderboard strategy.
3. Expand moderation and notification workflows if retained in product scope.

### Medium

1. Surface audit timestamps in relevant admin views.
2. Implement or remove roadmap fields such as users.google_id and users.profile_picture.

### Low

1. Keep generated reports lint-clean during future updates.
2. Maintain strict sync between admin module map and schema evolution.

## Supporting Docs

- README.md
- ANALYSIS_EXECUTIVE_SUMMARY.md
- DATABASE_FIELD_USAGE_REPORT.md
- QUICK_REFERENCE_UNUSED_FIELDS.md
- TABLE_FILE_INTERACTION_MATRIX.md
- AUDIT_AND_MODERNIZATION_SUMMARY.md

## Conclusion

Kody is stable for current LMS workflows and has a clear path for roadmap-level enhancements. The baseline is suitable for maintenance and iterative delivery.
