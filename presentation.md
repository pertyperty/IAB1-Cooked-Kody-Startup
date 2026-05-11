# Kody (LMS) — Final Project Presentation Script (20 minutes)

> Replace bracketed text like `[Presenter Name]` / `[Team Members]` with your actual details.

---

## 0:00–0:45 — Opening + Introductions

**[Presenter Name] (speaking):**
Good day! We are the **Cooked Group**, and this is our final project: **Kody**, a modern Learning Management System focused on **coding education** with **gamification**, **course management**, and **student progression tracking**.

Today’s flow follows the required structure:
1) System demonstration (CRUD + key workflows)  
2) Project Overview  
3) Data Warehousing concepts & reflection  
4) Big Data & Analytics concepts & reflection

---

## 0:45–3:00 — Project Overview (Origin • Purpose • Scope • Intended Users)

### Origin / Problem
**[Presenter Name] (speaking):**
We started with a common problem: many coding-learning platforms are either too complex for beginners or lack a clear structure for tracking progress. We wanted a system that:
- organizes lessons into courses and modules,
- tracks a learner’s progress clearly,
- supports hands-on practice via coding challenges,
- and motivates learners through gamification. For this prototype, we are only including the XP and leaderboards. No actual game has been made yet.

### Purpose
Kody’s purpose is to provide an end-to-end quasi-LMS workflow for coding education, with:
- authentication and role-based access control,
- course and content delivery,
- challenge submissions and progress tracking,
- and basic monetization via subscriptions and payments.

### Scope (What Kody includes)
Our system is a web application built with **PHP**, **MySQL**, **HTML/CSS**, and **JavaScript**.

From our schema and workflow, Kody covers these core areas:
- **Users & Auth**: `users`, `roles`, `user_roles`, `instructor_requests`
- **Learning Content**: `courses`, `modules`, `lessons`, `challenges`, `challenge_testcases`
- **Progress & Submissions**: `course_enrollment`, `user_progress`, `submissions`
- **Gamification**: `user_xp`, `leaderboard`
- **Monetization**: `subscription_plans`, `user_subscriptions`, `payments`
- **Governance & Messaging**: `moderation_reviews`, `notifications`

### Intended Users (Who it is for)
Kody is designed for:
- **Learners**: enroll, consume lessons, submit challenges, earn XP, see leaderboard.
- **Instructors**: create and manage courses/modules/lessons/challenges.
- **Admins**: full CRUD in the admin panel for core tables and moderation.
- **Moderators**: approve/reject submitted challenges and log moderation reviews.

---

## 3:00–14:00 — System Demonstration (10 minutes)

> Goal: Show working CRUD and core user journeys. Keep each step short and narrate what table(s) it touches.

### Demo Setup (say this quickly)
**[Presenter Name] (speaking):**
For this demo, our database is `kody_db` created from `database.sql`, with optional sample data from `seed_phase_0_5_rerun_safe.sql`.

### A) Auth + Role-based access (2 minutes)
**What to show**
1. Register a learner account (or log in).
2. Show session-based login and redirect to dashboard.

**What to say**
- Registration inserts into `users`, assigns default role via `user_roles`.
- Login validates credentials and starts session.

### B) Learner learning flow + CRUD touchpoints (4 minutes)
**What to show**
1. Browse courses (`courses` READ).
2. Enroll in a course (`course_enrollment` CREATE; `UNIQUE(user_id, course_id)` prevents duplicates).
3. Open a module/lesson (`modules`, `lessons` READ).
4. Open a challenge (`challenges`, `challenge_testcases` READ).
5. Submit code (`submissions` CREATE) and show status/score update if applicable.
6. Show progress update (`user_progress` CREATE/UPDATE) and XP (`user_xp` UPDATE).
7. Show leaderboard (`leaderboard` READ; derived from XP via joins in the app logic).

**What to say**
- “This is the transactional side (OLTP): lots of inserts and updates as the learner progresses.”
- “These tables become valuable later for analytics on performance and retention.”

### C) Subscription + payment (2 minutes)
**What to show**
1. Select plan (`subscription_plans` READ).
2. Activate subscription (`user_subscriptions` CREATE/UPDATE).
3. Record payment (`payments` CREATE) and show status.

**What to say**
- “This shows extended functionality beyond CRUD-only learning features.”

### D) Admin panel CRUD + moderation (2 minutes)
**What to show**
1. Admin login.
2. CRUD a course/module/lesson (CREATE, UPDATE, DELETE examples).
3. CRUD challenges/test cases or view submissions.
4. Show moderation review entry for a challenge (`moderation_reviews` CREATE).

**What to say**
- “Admin screens provide coverage over major tables and ensure we can manage content and governance.”

---

## 14:00–17:00 — Data Warehousing Concepts & Project Reflection

**[Presenter Name] (speaking):**
Kody is primarily an **OLTP** system: it supports frequent inserts/updates like enrollments, progress updates, submissions, and payments. Our current schema is normalized to support transactional integrity across these workflows.

### Where data warehousing fits (opportunities in our system)
Even though we are not a full data warehouse, Kody produces data that is ideal for analytics. Examples:
- `submissions` generates a history of attempts and outcomes (passed/failed/error).
- `course_enrollment` and `user_progress` track learning behavior over time.
- `payments` and `user_subscriptions` track monetization and retention.

### Suggested DW approach (reflection + improvement plan)
If we were to extend Kody for data warehousing, we would add:

1) **ETL / ELT pipeline**
- Extract from OLTP tables (submissions, enrollments, progress, payments)
- Transform into analytics-ready tables (cleaned timestamps, standardized keys, derived metrics)
- Load into a reporting schema (e.g., `kody_analytics`)

2) **Star-schema / dimensional modeling (example design)**
- **Facts**
  - `fact_submissions` (measures: score, status, attempt_count)
  - `fact_learning_activity` (measures: completions, time-to-complete)
  - `fact_payments` (measures: amount, successful_payment_flag)
- **Dimensions**
  - `dim_user`, `dim_course`, `dim_module`, `dim_challenge`, `dim_time`

3) **OLAP vs. OLTP separation**
- Keep OLTP for the application
- Use OLAP tables / aggregates for dashboards (faster queries, fewer joins during reporting)

4) **Performance and physical design improvements**
- Add indexes on common join/filter keys (e.g., `user_id`, `course_id`, `challenge_id`, timestamps)
- Add pre-aggregated summary tables or views for leaderboard and analytics dashboards

### Gap summary
Right now, our project stores the needed data for analytics, but we have not implemented:
- an ETL process,
- a dedicated analytics schema,
- or OLAP-optimized aggregates/materialized summaries.
These are clear improvement areas if Kody were deployed at larger scale or used for institutional reporting.

---

## 17:00–19:30 — Big Data & Analytics Concepts & Project Reflection

**[Presenter Name] (speaking):**
Kody is not a “big data” platform today, but it has multiple points where **big data and analytics principles** apply as usage grows.

### Big data lens (5Vs) applied to Kody
- **Volume**: many submissions over time (especially with multiple attempts per learner).
- **Velocity**: frequent events (progress updates, submissions, notifications).
- **Variety**: structured relational data + semi-structured content (lesson text, code submissions).
- **Veracity**: ensuring data quality (valid foreign keys, consistent statuses, timestamps).
- **Value**: actionable insights (drop-off points in courses, challenge difficulty tuning, retention).

### Analytics we can already support (using current OLTP data)
With the existing tables we can compute:
- completion rates per course/module (`user_progress`, `course_enrollment`)
- challenge pass/fail distribution (`submissions.execution_status`)
- top performers and XP levels (`user_xp`, `leaderboard`)
- subscription conversion and churn (`user_subscriptions`, `payments`)

### Reflection: what we would add to make it “analytics-ready at scale”
1) **Event logging**
- Add a `user_events` table (page views, clicks, time-on-lesson, attempts) for behavioral analytics.

2) **Scalability and query efficiency**
- Partition large fact-style tables by time (e.g., submissions by month)
- Cache hot aggregates (leaderboard, course summaries)
- Use asynchronous processing for expensive scoring/analytics jobs

3) **Dashboards / reporting features**
- Instructor dashboard: “which lessons have the most drop-offs?”
- Admin dashboard: “subscription conversion funnel and revenue trend”
- Learner dashboard: “time-to-solve, improvement over attempts”

### Gap summary
Our current system is analytics-capable in the sense that the core data is captured, but true big-data readiness would require:
- more detailed event capture,
- dedicated analytics storage/processing,
- and robust reporting features and performance strategies.

---

## 19:30–20:00 — Wrap-up
**[Presenter Name] (speaking):**
To conclude: Kody delivers a working LMS with full CRUD coverage across core entities, integrated database workflows, and a foundation for data warehousing and analytics. If extended, we would add an analytics schema, ETL, and richer event tracking to produce dashboards for instructors and admins.

Thank you—any questions?
