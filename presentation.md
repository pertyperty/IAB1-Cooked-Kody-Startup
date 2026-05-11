Presentation Flow — 20-minute deliverable

Objectives
- Students must design, develop, and present a Web-Based Application with full database CRUD functionality.
- Show understanding and application of IMDBSE2 concepts:
  1. Physical Database Design and Performance
  2. Database Application Development and Database Development Process
  3. Data Warehousing
  4. Big Data and Analytics

Structure & Timing (20 minutes total)

1) Title / Intro — 1:00
- Slide: Project name, team members, repo URL
- Speaker notes: One-sentence elevator pitch, course & instructor, branch to demo (e.g., `main`).

2) Team & Roles — 1:00
- Slide: Who did what (Narrative + Individual Contribution highlights)
- Speaker notes: Mention weekly documentation and GitHub activity (commits, issues, PR notes).

3) Project Overview — 3:00
- Slide: Origin, purpose, scope, intended users, major features
- Speaker notes: Explain tech stack (PHP, MySQL, front-end, any libs), core workflows (auth, courses, submissions, payments, leaderboard, admin CRUD).

4) Architecture & Physical Database Design (Performance) — 3:00
- Slide: Logical → physical mapping, schema highlights, indexes, FK relationships
- Speaker notes: Describe normalization choices, indexing strategy (which columns are indexed and why), partitioning / sharding considerations if any, query patterns and optimizations (prepared statements, LIMIT/OFFSET, pagination), caching opportunities.
- Visual aid: simple ER diagram or table-map screenshot.

5) Database Application Development & Process — 2:00
- Slide: Development process, CI/Git workflow, weekly docs, testing (smoke checks)
- Speaker notes: Explain CRUD layer architecture (generic admin CRUD renderer), shared helpers, how schema changes are managed and deployed.

6) Data Warehousing — 2:00
- Slide: Which elements fit a DW model (activity logs, submissions, payments, user_xp)
- Speaker notes: Describe candidate fact & dimension tables (submissions fact, user dim, challenge dim), ETL opportunities, gaps (missing staging, historical snapshots), and how to add a nightly ETL pipeline.

7) Big Data & Analytics — 2:00
- Slide: Where analytics could be applied (leaderboard trends, submission scoring, user engagement)
- Speaker notes: Candidate analytics (time-series XP trends, cohort analysis, challenge difficulty), scale considerations (moving data to columnar store, using Spark/Presto), and what extra telemetry to collect.

8) Demo Intro & Plan — 1:00
- Slide: Demo scope and success criteria
- Speaker notes: Outline exact actions you will perform during the live demo (login, create, edit, delete flows, show DB result, show a dashboard statistic).

9) Live System Demonstration (CRUD + extras) — 4:00
- Slide: Demo checklist (for audience reference)
- Speaker notes: Walkthrough of these steps as a live demo (recommended order):
  - Login (admin and user) — show role-based behavior (15s)
  - Create: new user/course/challenge/notification — verify success message (40s)
  - Read: search/pagination on CRUD table, view record details (30s)
  - Update: edit record (e.g. user profile or course) and show DB change (30s)
  - Delete: remove a test record and show effect (20s)
  - App-specific flows: submit_code -> process_submission (show submission created and score placeholder), instructor request flow, open notifications inbox, leaderboard refresh (90s)
- Tips: Use sample test accounts and a small dataset to make demo smooth.

10) Lessons Learned & Next Steps — 1:00
- Slide: What worked, what to improve, pending integrations (Judge0, Xendit), and production hardening steps
- Speaker notes: Mention technical debt items and how they map to project roadmap.

11) Q&A — 1:00
- Slide: Contact info, repo link, pointer to weekly docs

Demo Checklist (practical, copyable)
- Prepare: local server running, sample user accounts, small dataset
- Commands (local quick-run):

```powershell
# from project root
php -S localhost:8000 -t kody
# Then open http://localhost:8000 in browser
```

- Demo steps to perform:
  - Open admin: `kody/admin/index.php` — show stats
  - Open `users_crud.php`: Create → Read → Update → Delete (verify DB via phpMyAdmin or `SELECT`)
  - Submit code: `submit_code.php` → `process_submission.php` (show new `submissions` row)
  - Show `notifications.php` inbox and mark-as-read
  - Demonstrate instructor_request.php submit and admin review
  - Show `leaderboard.php` after triggering XP update (show persisted `rank_position` if populated)

Presentation Delivery Tips
- Rehearse with exactly 20 minutes: run demo at least once in the same environment
- Mute notifications and use a test dataset
- During live demo, narrate expected DB changes and show one quick SQL SELECT to confirm

GitHub & Deliverables Checklist
- Repository accessible with `main` branch containing code, `database.sql`, `README.md`, weekly docs (Narrative + Individual Contribution), and this `presentation.md`.
- Ensure `README.md` includes setup steps and demo script.
- Verify commit history and issues/pr notes for weekly updates.

Quick slide/visual suggestions
- Keep slides simple: 6–8 bullets or 1 diagram per slide
- Use screenshots (ER diagram, dashboard, sample CRUD table) to accelerate explanation
- Have a “pause slide” before demo with the checklist and exact URLs to open

File created: [presentation.md](presentation.md)

If you want, I can also generate a simple slide deck (PowerPoint or PDF) from this flow, or create speaker-facing cue cards. Which would you prefer next?

---

Detailed demo scripts & on-screen checklist
These are copy-pasteable commands, SQL snippets, and explicit UI steps to run during the 4-minute live demo portion so the demo is smooth and verifiable.

Quick environment startup (local):

```powershell
# from project root
php -S localhost:8000 -t kody
# in a second terminal (optional) tail logs (Windows PowerShell example)
Get-Content kody/assets/js/app.js -Wait
```

Database prep (one-time before demo)
- Run these SQL snippets in your local MySQL client or phpMyAdmin to create demo accounts and sample rows.

```sql
-- demo admin user (password: demo123)
INSERT INTO users (email, password_hash, name, role_id) VALUES
('admin@example.com', '$2y$10$EXAMPLEDUMMYHASHFORDEMO', 'Admin Demo', 1);

-- demo regular user
INSERT INTO users (email, password_hash, name, role_id) VALUES
('student@example.com', '$2y$10$DEMOUSERHASHHERE', 'Student Demo', 2);

-- sample course
INSERT INTO courses (title, description) VALUES ('Demo Course', 'Course for presentation');

-- sample challenge
INSERT INTO challenges (title, course_id) VALUES ('Hello World', 1);

-- sample notification
INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (2, 'Welcome to the demo!', 0, NOW());

-- seed leaderboard/user_xp for ranking
INSERT INTO user_xp (user_id, xp) VALUES (2, 1200);
UPDATE leaderboard SET rank_position = 1 WHERE user_id = 2;
```

Demo script: exact actions, screen items & timings (4:00 total)
- Prepare: open two windows/tabs: A) http://localhost:8000 B) DB client (phpMyAdmin or MySQL CLI)
- Start timer and follow these steps (clicks and what to show on-screen):

1. Login (admin) — 0:15
  - Navigate to `kody/login.php` in Tab A, enter `admin@example.com` and password `demo123`, show success toast.
  - On-screen: header showing admin links, unread notifications badge.

2. Admin dashboard stats — 0:20
  - Open `kody/admin/index.php`, show stat cards (Users, Courses, Submissions, Notifications).
  - On-screen: highlight stats, show where to click `Users` CRUD.

3. Create user via CRUD — 0:40
  - Click `Users` (admin CRUD) -> `Create` panel: fill `email` = `newuser@example.com`, `name` = `New User`, submit.
  - On-screen: show success message and the created row appearing in `Records` table.
  - Verification: Switch to Tab B (DB) and run:

```sql
SELECT id, email, name FROM users WHERE email = 'newuser@example.com';
```

4. Read / Search / Pagination — 0:30
  - In `users_crud.php`, use search field `q=` to filter `newuser@example.com`, show pagination if many rows.
  - On-screen: show the filtered results and the `Records` table caption.

5. Update record — 0:30
  - Click `Edit` on the new user, change `name` to `Edited User`, submit.
  - On-screen: show changed value in table and run (Tab B):

```sql
SELECT id, name FROM users WHERE email='newuser@example.com';
```

6. Delete record — 0:20
  - Click `Delete` on the record, confirm JS prompt. Show it disappears from the table.

7. Notifications inbox (user flow) — 0:40
  - In a fresh private-window, login as `student@example.com` (demo user). Open `kody/notifications.php`.
  - On-screen: show unread notification, click `Mark as read` and show unread badge decrement.
  - Verification SQL (Tab B):

```sql
SELECT id, user_id, message, is_read FROM notifications WHERE user_id = 2 ORDER BY created_at DESC LIMIT 5;
```

8. Submit code -> process_submission (app-specific flow) — 1:00
  - As `student@example.com` run `kody/submit_code.php` for the `Hello World` challenge. Submit sample code and show the success flow.
  - Immediately open `kody/admin/submissions_crud.php` (admin) to show new `submissions` row.
  - On-screen: show `execution_status` (placeholder) and `score` column (if present). Run verification SQL:

```sql
SELECT id, user_id, challenge_id, status, score FROM submissions ORDER BY id DESC LIMIT 5;
```

Demo fallback & screenshots (if live demo fails)
- Have these screenshots ready and open in a folder: `screenshots/admin-dashboard.png`, `screenshots/users-create.png`, `screenshots/notifications-inbox.png`, `screenshots/submission-row.png`, `screenshots/leaderboard.png`.
- If the live demo fails, switch to screenshots and narrate the same verification SQL queries and timing.

Quick verification SQL snippets (copyable during Q&A)

```sql
-- count users
SELECT COUNT(*) FROM users;

-- recent submissions
SELECT * FROM submissions ORDER BY id DESC LIMIT 10;

-- leaderboard top 10
SELECT u.id, u.name, l.rank_position, ux.xp
FROM leaderboard l
JOIN users u ON u.id = l.user_id
LEFT JOIN user_xp ux ON ux.user_id = u.id
ORDER BY l.rank_position ASC
LIMIT 10;
```

Speaker cues while demoing
- Narrate: "Now I'll create a user — you should see the success toast and the record appear." (then run SQL to confirm if asked)
- If asked for performance: show `EXPLAIN` for typical paginated query (admin CRUD uses LIMIT/OFFSET). Example:

```sql
EXPLAIN SELECT * FROM users WHERE email LIKE '%example%' LIMIT 25 OFFSET 0;
```

Wrap-up: post-demo checks (30s)
- Confirm notifications read flag updated, confirm submission row exists, confirm leaderboard shows expected ranks.
- If time allows, show a quick query in Tab B to show counts and timestamps.

---

I updated `presentation.md` with these scripts and exact on-screen actions. Next task on the todo list: produce speaker cue cards for each slide. Shall I generate cue cards now? 