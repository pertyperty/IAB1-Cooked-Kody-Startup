# Kody Website

Kody is now structured as a multi-page working website for the SRS instead of a single auth page plus one large workspace page.

The current build provides:

- dedicated pages for login, registration, verification, recovery, homepage, profile, learning, creator work, rewards, finance, and governance
- working local authentication, session-token usage, and lockout behavior
- role-based page visibility for learner, contributor, instructor, moderator, and administrator
- working local flows for the SRS use cases across modules A-G
- learner-or-instructor registration flow with extra instructor verification fields on signup
- sticky top navigation across the website
- actual page-first interactions for learning modules and KodeBits package selection, with direct testing tools placed below the main page experience
- simulated external-service interfaces for OAuth, email delivery, payment, cloud storage, and code execution without integrating those real providers yet

## Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL

## Website Structure

### Public pages

- `public/index.php` - landing homepage
- `public/login.php` - login
- `public/register.php` - registration
- `public/verify.php` - email verification
- `public/recover.php` - account recovery

### Authenticated pages

- `public/home.php` - logged-in homepage
- `public/profile.php` - account management
- `public/learn.php` - courses, modules, challenges, FAQ, feedback
- `public/creator.php` - content and challenge authoring
- `public/rewards.php` - gamification and weekly challenge flows
- `public/finance.php` - token purchase, spending, earnings, payouts
- `public/governance.php` - moderation and administration
- `public/app.php` - legacy redirect to `home.php`

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

- Password hashing is still prototype-grade and should be upgraded to `password_hash` / `password_verify`.
- The backend is local-first and does not call the real external APIs yet.
- The website is intended to show working end-to-end behavior using the local database and simulated provider states.
- `IMPLEMENTATION.md` is the active iteration tracker and should be updated every pass.

## Recommended Next Steps

1. Add direct row-level actions so creator and governance tasks rely less on manual IDs.
2. Add server-side hardening: CSRF, stronger validation, safer password handling, and stricter session control.
3. Add scenario-based testing for the role matrix and the 61 use cases.
