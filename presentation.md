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
