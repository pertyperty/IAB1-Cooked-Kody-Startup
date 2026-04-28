# Kody Implementation Tracker

Last updated: 2026-04-27

This file tracks the current state of the Kody website and must be updated every iteration together with `README.md`.

## Status Legend

- `Working website page`: implemented through a dedicated or shared website page and backed by API + DB actions.
- `Working local simulation`: implemented end to end locally, while external-provider behavior is still simulated.
- `Hardening pending`: security, reliability, or UX depth still needs more work.

## Current Iteration Goal

Audit the multi-page system, clean up interface boundaries, and make the separated webpage structure more explicit inside the product.

## Completed In This Iteration

- Audited the system structure and documented the main hardening gaps:
  - missing CSRF protection
  - wildcard CORS policy
  - monolithic API router
  - client-side auth bootstrap limiting server-side guards
  - missing scenario-based regression coverage
- Simplified `public/index.php` into a real gateway page that routes users into dedicated auth/workspace pages instead of embedding multiple public forms together.
- Added reusable interface rails in `public/includes/site.php` for:
  - public authentication pages
  - learning pages
  - wallet/finance pages
  - major workspace operational areas
- Applied the shared interface rails across the public and authenticated page set for cleaner separation and page-to-page wayfinding.

- Split public authentication into dedicated pages:
  - `index.php` (auth-first gateway with routing into dedicated public pages)
  - `login.php`
  - `register.php`
  - `verify.php`
  - `recover.php`
- Split authenticated experience into dedicated pages:
  - `home.php`
  - `profile.php`
  - `learn.php`
  - `creator.php`
  - `rewards.php`
  - `finance.php`
  - `governance.php`
- Added shared PHP layout helpers in `public/includes/site.php`.
- Converted `public/app.php` into a proper legacy entry webpage with direct links to current flows.
- Reworked frontend scripts so the new page structure still uses the working backend flows.
- Preserved working lockout logic:
  - 3 failed attempts = 15 minutes
  - 6 failed attempts = 1 hour
  - 9 failed attempts = 24 hours
- Preserved RBAC page visibility for:
  - learner
  - contributor
  - instructor
  - moderator
  - administrator
- Continued showing external-service style interfaces without real integration:
  - OAuth
  - email delivery
  - judge/execution service
  - payment provider
  - cloud/hosted asset interface
- Updated registration so only learner or instructor can be selected.
- Added instructor-specific registration fields for verification at signup.
- Moved contributor application into the learner profile flow.
- Made the top navigation sticky.
- Upgraded password security:
  - new and reset passwords now use `password_hash`
  - login checks use `password_verify`
  - legacy SHA-256 seed hashes are auto-upgraded on successful login
- Improved actual page interactions:
  - clickable course-module opening flow
  - clickable standalone module opening flow
  - clickable KodeBits package selection flow
- Added direct row-level operational actions for creator and governance tables:
  - course/module/challenge edit, archive, and delete shortcuts
  - contributor request approve/reject shortcuts
  - instructor credential accept/reject shortcuts
  - user suspend/reinstate/update-prefill shortcuts
  - report moderation shortcuts
- Added homepage operational shortcut cards that route users to the right page per workflow and role.
- Added stricter backend auth-input validation:
  - email format validation on registration, recovery request, and account email change
  - password strength checks for registration, recovery reset, and account password change
  - minimum full-name length on registration
- Moved direct/manual testing controls below the main page experience on key pages.

## Phase Plan

## Phase 1. Backend and Data Baseline (Completed)

- schema and seed data established in `kody-db.sql`
- PHP API router implemented for A-G feature groups
- session-token based authentication, audit logs, notifications, and lockout support implemented

## Phase 2. Single-Page Website Baseline (Completed)

- working auth flow and one large RBAC workspace were established
- broad SRS use-case coverage was connected to the API

## Phase 3. Multi-Page Website Conversion (Completed)

- public and authenticated flows are now separated into actual website pages
- shared layout/navigation structure added
- dedicated pages now exist for homepage, profile, learning, creator work, rewards, finance, and governance

## Phase 4. UX Depth and Record-Level Actions (Completed)

- Reduced manual ID entry for creator/moderator/admin tasks with direct row actions.
- Added richer row-level actions from course/module/challenge/report/user tables.
- Added homepage workflow shortcuts for faster page-to-page navigation.

## Phase 5. Security and Reliability Hardening (In Progress)

- password hashing upgrade completed with backward compatibility
- system audit completed and documented
- add CSRF protection
- add stricter validation and error handling
- review and tighten session safety

## Phase 6. Test and Release Readiness (Pending)

- add scenario-based verification for the 61 use cases
- add role-matrix testing
- add deployment guidance and environment setup documentation

## Use Case Coverage Matrix

## A. Account and Authentication Management

- `UC A01` User Login: Working website page, Hardening pending.
- `UC A02` Recover Account: Working website page, Hardening pending.
- `UC A03` Register Account: Working website page, Hardening pending.
- `UC A04` Verify Email: Working website page, Hardening pending.
- `UC A05` Request Contributor Role: Working website page, Hardening pending.
- `UC A06` Verify Instructor Credentials: Working website page, Hardening pending.
- `UC A07` View Account: Working website page, Hardening pending.
- `UC A08` Edit Account: Working website page, Hardening pending.
- `UC A09` Archive Account: Working website page, Hardening pending.
- `UC A10` Delete Account: Working website page, Hardening pending.

## B. Content Management

- `UC B01` Create Course: Working website page, Hardening pending.
- `UC B02` Edit Course: Working website page, Hardening pending.
- `UC B03` Archive Course: Working website page, Hardening pending.
- `UC B04` Delete Course: Working website page, Hardening pending.
- `UC B05` Create Module: Working website page, Hardening pending.
- `UC B06` Edit Module: Working website page, Hardening pending.
- `UC B07` Archive Module: Working website page, Hardening pending.
- `UC B08` Delete Module: Working website page, Hardening pending.
- `UC B09` Assign Module to Course: Working website page, Hardening pending.

## C. Challenge Management

- `UC C01` Create Code Challenge: Working website page, Hardening pending.
- `UC C02` Edit Code Challenge: Working website page, Hardening pending.
- `UC C03` Archive Code Challenge: Working website page, Hardening pending.
- `UC C04` Delete Code Challenge: Working website page, Hardening pending.
- `UC C05` Approve Code Challenge: Working website page, Hardening pending.
- `UC C06` Submit Code Solution: Working website page, Working local simulation.
- `UC C07` Evaluate Code Solution: Working website page, Working local simulation.

## D. Gamification and Rewards System

- `UC D01` Create Gamified Activity from Preset: Working website page, Working local simulation.
- `UC D02` Grant Rewards: Working website page, Working local simulation.
- `UC D03` Determine Ranking: Working website page, Working local simulation.
- `UC D04` Configure Weekly Challenge: Working website page, Working local simulation.
- `UC D05` Evaluate Weekly Submissions: Working website page, Working local simulation.
- `UC D06` Publish Weekly Results: Working website page, Working local simulation.

## E. User Interaction and Engagement

- `UC E01` View User Dashboard: Working website page, Hardening pending.
- `UC E02` Browse Courses and Modules: Working website page, Hardening pending.
- `UC E03` Browse Challenges: Working website page, Hardening pending.
- `UC E04` Enroll in Course: Working website page, Hardening pending.
- `UC E05` Access Course Module: Working website page, Hardening pending.
- `UC E06` Access Standalone Module: Working website page, Hardening pending.
- `UC E07` Participate in Challenge: Working website page, Working local simulation.
- `UC E08` View Execution Feedback: Working website page, Working local simulation.
- `UC E09` View Leaderboard: Working website page, Working local simulation.
- `UC E10` React to Content: Working website page, Working local simulation.
- `UC E11` View FAQ: Working website page, Working local simulation.

## F. Transaction and Earnings Management

- `UC F01` Purchase Tokens: Working website page, Working local simulation.
- `UC F02` Use Tokens: Working website page, Working local simulation.
- `UC F03` View Publisher Earnings: Working website page, Working local simulation.
- `UC F04` Receive Earnings: Working website page, Working local simulation.
- `UC F05` Receive Notifications: Working website page, Working local simulation.

## G. Administration and Governance

- `UC G01` Moderate Content: Working website page, Working local simulation.
- `UC G02` View System Reports: Working website page, Working local simulation.
- `UC G03` Approve Contributor Requests: Working website page, Working local simulation.
- `UC G04` Suspend User Account: Working website page, Working local simulation.
- `UC G05` Reinstate User Account: Working website page, Working local simulation.
- `UC G06` View User Accounts: Working website page, Working local simulation.
- `UC G07` Update User Account: Working website page, Working local simulation.
- `UC G08` Create Game Preset: Working website page, Working local simulation.
- `UC G09` Update Game Preset: Working website page, Working local simulation.
- `UC G10` Delete Game Preset: Working website page, Working local simulation.
- `UC G11` Create FAQ Entry: Working website page, Working local simulation.
- `UC G12` Update FAQ Entry: Working website page, Working local simulation.
- `UC G13` Delete FAQ Entry: Working website page, Working local simulation.

## Immediate Next Slice

1. Add CSRF protection across public and authenticated write operations.
2. Replace `Access-Control-Allow-Origin: *` with environment-driven allowlisted origins.
3. Split the API router into module files or controllers.
4. Tighten session-control posture (rotation cadence, client storage strategy, and optional server-side page guards).
5. Add scenario-based verification scripts for role matrix and high-risk use cases.
