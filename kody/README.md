# Kody Working Website Demo

Kody is now structured as a multi-page working website for the SRS instead of a single auth page plus one large workspace page.

The current build provides:

- dedicated pages for login, registration, verification, recovery, homepage, profile, learning, creator work, rewards, finance, and governance
- interface rails that group related public, learning, wallet, and workspace pages so the separated website structure is visible from inside each area
- a simplified public gateway page that routes users into dedicated interfaces instead of embedding login and registration on the same entry screen
- working local authentication, session-token usage, and lockout behavior
- role-based page visibility for learner, contributor, instructor, moderator, and administrator
- working local flows for the SRS use cases across modules A-G
- learner-or-instructor registration flow with extra instructor verification fields on signup
- sticky top navigation across the website
- actual page-first interactions for learning modules and KodeBits package selection, with direct testing tools placed below the main page experience
- direct row-level operations from creator and governance tables so common tasks no longer require manual ID typing
- homepage workflow shortcuts that route users to the right operational page per role
- stricter server-side input validation for email and password updates in key auth flows
- simulated external-service interfaces for OAuth, email delivery, payment, cloud storage, and code execution without integrating those real providers yet

## Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL

## Website Structure

### Public pages

- `public/index.php` - website entry page with auth-start actions
- `public/login.php` - login
- `public/register.php` - registration
- `public/verify.php` - email verification
- `public/recover.php` - account recovery
- `public/app.php` - legacy entry page with links to current flows

### Authenticated pages

- `public/home.php` - logged-in homepage
- `public/profile.php` - account management
- `public/learn.php` - courses, modules, challenges, FAQ, feedback
- `public/creator.php` - content and challenge authoring
- `public/rewards.php` - gamification and weekly challenge flows
- `public/finance.php` - token purchase, spending, earnings, payouts
- `public/governance.php` - moderation and administration

### Shared frontend helpers

- `public/includes/site.php` - shared layout helpers
- `public/assets/js/auth.js` - public auth-page behavior
- `public/assets/js/dashboard.js` - authenticated page behavior
- `public/assets/css/styles.css` - shared site styling

## Current Behavior

### Authentication

- `UC A01` login works
- `UC A02` recovery works for non-admin accounts
- `UC A03` registration works
- `UC A04` verification works
- registration options are now limited to `Learner` and `Instructor`
- instructor signup requires extra verification inputs during registration
- lockout policy is enforced:
  - 3 failed attempts = 15 minutes
  - 6 failed attempts = 1 hour
  - 9 failed attempts = 24 hours

### Role model

- Learner
- Contributor inherits learner behavior
- Instructor inherits contributor + learner behavior
- Moderator inherits learner behavior
- Administrator has all SRS use cases except account recovery
- Learners request contributor access from the profile page

### External-service interfaces

The following are intentionally shown as integrated interfaces while still running locally:

- OAuth sign-in interface
- email delivery interface
- code-execution/judge interface
- payment and payout interface
- cloud storage / hosted file URL interface

## Database Setup

1. Import `kody-db.sql` into MySQL.
2. Configure environment variables if needed:

- `KODY_DB_HOST`
- `KODY_DB_PORT`
- `KODY_DB_NAME`
- `KODY_DB_USER`
- `KODY_DB_PASS`

## Run Locally

From inside the `kody` folder:

```bash
php -S localhost:8000 -t .
```

Open:

- `http://localhost:8000/public/index.php`

## Seed Accounts

- `admin@kody.local` / `admin123`
- `moderator@kody.local` / `moderator123`
- `instructor@kody.local` / `instructor123`
- `contributor@kody.local` / `contributor123`
- `learner@kody.local` / `learner123`

## Current Notes

- Password handling now uses `password_hash` and `password_verify` with automatic upgrade from legacy SHA-256 seed hashes after successful login.
- The backend is local-first and does not call the real external APIs yet.
- The website is intended to show working end-to-end behavior using the local database and simulated provider states.
- `IMPLEMENTATION.md` is the active iteration tracker and should be updated every pass.

## System Audit Snapshot

This pass confirms that the system is functionally broad and much clearer at the page level, but it still has several architectural hardening gaps:

- `api/index.php` remains a large monolithic router and would benefit from module-level extraction.
- Write actions still need CSRF protection.
- The API still sends `Access-Control-Allow-Origin: *`, which is too permissive for a hardened deployment.
- Auth bootstrapping still depends on client-side stored session state, which limits true server-side page guarding.
- Scenario-based regression checks for the role matrix and high-risk workflows are still missing.

## Recommended Next Steps

1. Add CSRF protection across write operations.
2. Replace the wildcard CORS policy with an environment-specific allowlist.
3. Split `api/index.php` into module handlers or controllers for safer maintenance.
4. Tighten session control strategy and add optional server-side page guards for authenticated pages.
5. Add scenario-based testing for the role matrix and the 61 use cases.
