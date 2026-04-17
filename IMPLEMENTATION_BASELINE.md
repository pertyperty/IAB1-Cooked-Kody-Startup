# Kody Implementation Baseline (Beginner-Friendly)

This file is your step-by-step development guide for building Kody.
Follow the phases in order. Keep each phase small and test before moving on.

## How to Use This Guide

- Build one section at a time. (Alternate between 3 members)
- Test each page manually in browser.
- Commit your progress after each section.
- If a section fails, fix it first before adding new features.

## Phase 0 - Environment Setup

### Tasks

- Install XAMPP (or any PHP + MySQL stack).
- Start Apache and MySQL.
- Create database `kody_db`.
- Run the SQL schema from `README.md`.
- Put project folder in web root (for XAMPP, `htdocs`).
- Confirm `http://localhost/IMDBSE2 Software/index.php` opens.

### Output of this phase

- Project opens in browser.
- Database tables are created.

## Phase 0.5 - Seed Data

### Tasks

- Insert at least:
  - 2 users (1 learner, 1 admin)
  - 2 courses
  - 2 modules per course
  - 2 lessons per module
  - 2 challenges per module
- Insert test XP into `user_xp`
- Insert sample subscription plans

### Output of this phase

- System has visible data for testing
- Dashboard, course, and leaderboard pages are not empty

## Phase 1 - Base System Files

### Tasks

- Configure database connection in `includes/db.php`.
- Create session/auth helper functions in `includes/auth.php`.
- Add reusable DB functions in `includes/functions.php`.
- Confirm `includes/header.php` and `includes/footer.php` render correctly.
- Confirm CSS and JS load from `assets/css/style.css` and `assets/js/app.js`.
- Add a navigation bar in `header.php` with links to:
  - Dashboard
  - Courses
  - Leaderboard
  - Subscription
  - Admin Panel
  - Logout

### Output of this phase

- Shared includes work for all pages.
- No path errors for include files.

## Phase 2 - Authentication Flow

### Tasks

- Implement register logic in `register.php`:
  - Validate fields.
  - Hash password using `password_hash`.
  - Insert into `users` table.
- Implement login logic in `login.php`:
  - Find user by email.
  - Verify password using `password_verify`.
  - Store user session values.
- Keep `logout.php` to destroy session.
- Verify `index.php` redirects correctly.

### Output of this phase

- User can register, login, logout.
- Session-based redirect works.

## Phase 3 - Dashboard and Core Read Pages

### Tasks

- In `dashboard.php`, display:
  - User basic info.
  - Current XP from `user_xp`.
  - Enrolled courses from `course_enrollment` + `courses`.
- In `course.php`, show selected course details, modules, and lessons.
- In `leaderboard.php`, display users ranked by XP.

### Output of this phase

- Core READ screens are visible and connected to DB.

## Phase 4 - Enrollment and Progress

### Tasks

- Implement enrollment action in `enroll.php`:
  - Prevent duplicate enrollment.
  - Insert into `course_enrollment`.
- Implement progress updates in `progress.php`:
  - Show current progress rows.
  - Update status and `completed_at` where needed.

### Output of this phase

- User can enroll in courses.
- Progress records update correctly.

## Phase 5 - Coding Submission and XP

### Tasks

- Build submission form in `submit_code.php`.
- In `process_submission.php`:
  - Insert submission into `submissions`.
  - Set status (`pending`, `passed`, `failed`, `error`).
  - If passed, call XP update logic.
  - Update related progress row to completed.

### Output of this phase

- Submission flow updates `submissions`, `user_xp`, and `user_progress`.

## Phase 5.5 - XP Logic Isolation

### Tasks

- Move XP logic into a reusable function:
  - awardXP(user_id, xp)
- Call this function from submission flow

### Output

- XP system is reusable and consistent

## Phase 6 - Subscription and Payment Simulation

### Tasks

- Show plans in `subscription.php` from `subscription_plans`.
- In `payment.php`:
  - Simulate payment creation in `payments`.
  - Create or update `user_subscriptions`.
  - Set status values correctly.

### Output of this phase

- Basic monetization simulation works.

## Phase 7 - Generic CRUD Actions

### Rules

- Each request must include:
  - table name
  - allowed fields
- NEVER allow raw table/column input from user

### Required structure

- actions/create.php
  - Insert record using prepared statement
- actions/update.php
  - Update record using primary key
- actions/delete.php
  - Delete record using primary key

### Required function format

function createRecord($table, $data)
function updateRecord($table, $id, $data)
function deleteRecord($table, $id)

### Safety rules

- Use whitelist for tables
- Use whitelist for columns
- Reject unknown fields

### Output of this phase

- All admin pages reuse centralized CRUD logic
- No duplicated SQL across files

## Phase 8 - Admin CRUD Pages (By Group)

Build each admin page in small groups.

### Group A (User + Role)

- `admin/users_crud.php`
- `admin/roles_crud.php`
- `admin/user_roles_crud.php`
- `admin/instructor_requests_crud.php`

### Group B (Learning Content)

- `admin/courses_crud.php`
- `admin/modules_crud.php`
- `admin/lessons_crud.php`
- `admin/challenges_crud.php`
- `admin/testcases_crud.php`

### Group C (Learning Activity)

- `admin/submissions_crud.php`
- `admin/user_xp_crud.php`
- `admin/leaderboard_crud.php`
- `admin/enrollment_crud.php`
- `admin/progress_crud.php`

### Group D (Monetization + Notifications)

- `admin/subscriptions_crud.php`
- `admin/payments_crud.php`
- `admin/notifications_crud.php`

### For each admin page, implement

- Create form.
- Records table (read).
- Update action (edit row).
- Delete action.
- Success/error message display.

### Output of this phase

- Full CRUD coverage across required tables.

## Phase 9 - Validation, Security, and Cleanup

### Tasks

- Validate all `$_POST` inputs.
- Escape output with `htmlspecialchars`.
- Use prepared statements everywhere.
- Add simple role checks for admin pages.
- Standardize page layout and navigation links.

### Output of this phase

- Cleaner, safer, and more consistent app.

## Phase 10 - Final Testing Checklist

### Functional checks

- Register/Login/Logout works.
- Dashboard displays correct user data.
- Enrollment and progress update correctly.
- Submission updates XP and progress.
- Leaderboard ordering is correct.
- Subscription/payment flow stores records.
- Every admin page supports create/read/update/delete.

### Database checks

- Foreign key relations stay valid.
- No duplicate enrollment.
- No SQL errors in normal flow.

### Demo readiness checks

- Navigation is clear.
- No broken links.
- No blank pages.
- Error messages are understandable.

## Prompting Template for Future Work

Use this prompt pattern when asking for help per file:

1. Target file: (example `admin/users_crud.php`)
2. Goal: (example implement CREATE + READ first)
3. Fields needed: (list table columns)
4. Rules: (foreign keys, validation)
5. Done when: (what should work)

Example:

"Implement CREATE and READ in `admin/users_crud.php` using prepared statements. Keep it beginner-friendly and explain the code in simple terms."

## Notes

- Keep solutions simple first, then improve.
- Avoid building all pages at once.
- Finish one working flow before moving to the next.

## Demo Flow Script (Presentation Guide)

1. Register a new user
2. Login
3. View dashboard (initial state)
4. Enroll in a course
5. Submit code (simulate pass)
6. Show XP increase
7. Show progress update
8. Open leaderboard (rank changes)
9. Open admin panel
10. Perform CRUD (create/edit/delete record)

Goal: Show cause → database change → visible result