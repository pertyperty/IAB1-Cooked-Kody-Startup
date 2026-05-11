# Kody - Modern Learning Management System

A full-featured web application for online coding education with gamification, course management, and student progression tracking.

---

## Overview

Kody is a Learning Management System (LMS) built with PHP, MySQL, HTML5, modern CSS, and vanilla JavaScript.

### System validates

- Enterprise-grade relational database design.
- Full CRUD operations across core entities.
- Database integration with user and admin interfaces.
- Real workflow coverage: authentication, enrollment, progress, submissions, subscriptions.
- Modern dark UI with responsive behavior.

---

## Architecture and Stack

| Layer | Technology |
| ----- | ---------- |
| Frontend | HTML5, CSS (dark UI), Vanilla JavaScript |
| Backend | PHP 7.4+ |
| Database | MySQL 8.0+ |
| UX | Accessibility-first, responsive layout |

### Design principles

- Modular structure for maintainability.
- Security-first defaults (password hashing, prepared statements, sanitization).
- Dark UI with consistent component styling.
- Accessibility and keyboard-focus support.

---

## Project Structure

```text
/kody/
├── index.php
├── login.php
├── register.php
├── logout.php
├── dashboard.php
├── course.php
├── enroll.php
├── progress.php
├── submit_code.php
├── process_submission.php
├── leaderboard.php
├── subscription.php
├── payment.php
├── actions/
│   ├── create.php
│   ├── update.php
│   └── delete.php
├── admin/
│   ├── index.php
│   ├── users_crud.php
│   ├── roles_crud.php
│   ├── user_roles_crud.php
│   ├── instructor_requests_crud.php
│   ├── courses_crud.php
│   ├── modules_crud.php
│   ├── lessons_crud.php
│   ├── challenges_crud.php
│   ├── testcases_crud.php
│   ├── submissions_crud.php
│   ├── user_xp_crud.php
│   ├── leaderboard_crud.php
│   ├── enrollment_crud.php
│   ├── progress_crud.php
│   ├── subscriptions_crud.php
│   ├── user_subscriptions_crud.php
│   ├── payments_crud.php
│   ├── moderation_reviews_crud.php
│   ├── notifications_crud.php
│   └── edit.php
├── includes/
│   ├── db.php
│   ├── auth.php
│   ├── functions.php
│   ├── admin_crud.php
│   ├── header.php
│   └── footer.php
└── assets/
    ├── css/
    │   ├── style.css
    │   └── admin.css
    └── js/
        └── app.js
```

---

## Core Workflows

### Registration and login

Files: `register.php`, `login.php`, `logout.php`.

```text
1) User submits registration form
2) System validates fields and uniqueness
3) Password is hashed
4) User row is inserted
5) Learner role is assigned
6) User logs in and starts a session
```

### Learning flow

Files: `course.php`, `enroll.php`, `progress.php`, `submit_code.php`, `process_submission.php`.

```text
1) Browse courses
2) Enroll in a course
3) Open modules and lessons
4) Submit challenge solution
5) Record submission status and score
6) Update progress and XP
```

### Subscription and payment

Files: `subscription.php`, `payment.php`.

```text
1) User selects a plan
2) Payment record is created
3) Subscription is activated or updated
4) Status and dates are persisted
```

---

## Role-Based Access Control

Tables: `roles`, `user_roles`.

- `admin`: full system and admin panel access.
- `instructor`: course/content authoring role.
- `learner`: core learning access.
- `contributor`: challenge contribution role.
- `moderator`: moderation and review role.

---

## Database Coverage

### Main schema groups

| Category | Tables |
| -------- | ------ |
| Users and Auth | users, roles, user_roles, instructor_requests |
| Learning | courses, modules, lessons, challenges, challenge_testcases |
| Progress and Submissions | submissions, user_progress, course_enrollment |
| Gamification | user_xp, leaderboard |
| Monetization | subscription_plans, user_subscriptions, payments |
| Governance and Messaging | moderation_reviews, notifications |

### Notes from audit

- Most core workflow tables are actively used.
- Some schema fields are currently reserved for future features.
- Admin CRUD coverage includes all major tables, including `user_subscriptions` and `moderation_reviews`.

---

## UI and Theme

- Dark theme tokens are centralized in `assets/css/style.css`.
- Admin visual system and table styles are in `assets/css/admin.css`.
- Common links/buttons/inputs are normalized for consistent rendering.
- Scroll-position retention is handled in `assets/js/app.js` to avoid jumping to top after same-page actions.

---

## Getting Started

### Prerequisites

- PHP 7.4+
- MySQL 8.0+
- Apache/Nginx (or XAMPP/LAMP/LEMP)

### Setup

```bash
# 1) Create schema
mysql -u root -p < database.sql

# 2) Optional seed data
mysql -u root -p kody_db < seed_phase_0_5_rerun_safe.sql

# 3) Configure DB credentials
# edit: kody/includes/db.php

# 4) Open app
# http://localhost/kody/index.php
```

---

## Validation Scripts

```bash
php scripts/check_php_syntax.php
php scripts/run_smoke_checks.php
```

If local `php` is unavailable in shell, use your IDE problem panel and web runtime checks.

---

## Common Issues

| Issue | Cause | Fix |
| ----- | ----- | --- |
| Database connection failed | Invalid credentials | Update `includes/db.php` |
| Blank page | PHP error | Check error log and syntax |
| CSS not loading | Wrong path | Confirm files under `assets/css/` |
| Admin page denied | Role/session mismatch | Ensure user has admin role |

---

## Contributing

1. Create a branch from `main`.
2. Keep changes scoped and tested.
3. Run smoke/syntax checks when available.
4. Open a pull request with a clear summary.

---

## Status

Production-ready baseline with modern dark UI, end-to-end core workflows, and broad CRUD/admin coverage.
