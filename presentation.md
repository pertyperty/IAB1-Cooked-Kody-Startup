# Kody (quasi-LMS) - Final Project Presentation Script (20 minutes)

> Replace bracketed text like `[Presenter Name]` / `[Team Members]` / `[Demo Account]` with your actual details.

---

## 0:00-0:45 - Opening + Introductions

**[Presenter Name] (speaking):**
Good day! We are the **Cooked Group** - I am Mark Cyrus P Macaraeg, our team's presenter, with Rupert C dela Cruz as our Backend developer, he is in the background as the camera man for this presentation and then Asher Nathaniel E. De Guzman, another Backend developer, here with me physically, providing moral support - and this is our final project: **Kody**, a modern Learning Management System focused on **coding education** with **gamification**, **course management**, and **student progression tracking**.

**[Presenter Name] (speaking):**
In the next 20 minutes, we will follow the required flow:
1) System demonstration (CRUD + key workflows)  
2) Project Overview  
3) Data Warehousing concepts and reflection  
4) Big Data and Analytics concepts and reflection

**[Presenter Name] (speaking):**
As we demo, we will briefly mention which database tables are affected so it is clear where CRUD happens.

---

## 0:45-3:00 - Project Overview (Origin, Purpose, Scope, Intended Users)

### Origin / Problem
**[Presenter Name] (speaking):**
We started with a common problem: many coding-learning platforms are either too complex for beginners or lack a clear structure for tracking progress. We wanted a system that:
- organizes lessons into courses and modules,
- tracks a learner's progress clearly,
- supports hands-on practice via coding challenges,
- and motivates learners through gamification.

**[Presenter Name] (speaking):**
For this prototype, we focused on **XP and leaderboards** as the gamification component. There is no full game yet, but the motivation loop is already present.

### Purpose
**[Presenter Name] (speaking):**
Kody's purpose is to provide an end-to-end quasi-LMS workflow for coding education, with:
- authentication and role-based access control,
- course and content delivery,
- challenge submissions and progress tracking,
- and basic monetization via subscriptions and payments.

### Scope (What Kody includes)
**[Presenter Name] (speaking):**
Our system is a web application built with **PHP**, **MySQL**, **HTML/CSS**, and **JavaScript**.

From our schema and workflow, Kody covers these core areas:
- **Users and Auth**: `users`, `roles`, `user_roles`, `instructor_requests`
- **Learning Content**: `courses`, `modules`, `lessons`, `challenges`, `challenge_testcases`
- **Progress and Submissions**: `course_enrollment`, `user_progress`, `submissions`
- **Gamification**: `user_xp`, `leaderboard`
- **Monetization**: `subscription_plans`, `user_subscriptions`, `payments`
- **Governance and Messaging**: `moderation_reviews`, `notifications`

### Intended Users (Who it is for)
**[Presenter Name] (speaking):**
Kody is designed for four user types:
- **Learners**: enroll, consume lessons, submit challenges, earn XP, see leaderboard.
- **Instructors**: create and manage courses/modules/lessons/challenges.
- **Admins**: full CRUD in the admin panel for core tables and moderation.
- **Moderators**: approve/reject submitted challenges and log moderation reviews.

**[Presenter Name] (speaking):**
Now we will move to the system demo to show CRUD and the key workflows end-to-end.

---

## 3:00-14:00 - System Demonstration (11 minutes)

> Goal: Show working CRUD and core user journeys. Keep each step short and narrate what table(s) it touches.

### Demo Setup (say this quickly)
**[Presenter Name] (speaking):**
For this demo, our database is `kody_db` created from `database.sql`, with optional sample data from `seed_phase_0_5_rerun_safe.sql`.

**[Presenter Name] (speaking):**
If you see pre-filled courses and accounts, that is from seed data. If not, we can still demonstrate CRUD by creating entries live.

### A) Auth + Role-based access (2 minutes)

**What to show**
1. Register a learner account (or log in to `[Demo Account]`).
2. Show session-based login and redirect to dashboard.

**Specific lines to say (verbatim-ready)**
**[Presenter Name] (speaking):**
I will start by registering a learner account.

**[Presenter Name] (speaking):**
This inserts into `users`, then assigns the default learner role via `user_roles`.

**[Presenter Name] (speaking):**
Now I will log in. The system validates credentials, starts a session, and redirects us to the learner dashboard based on our role.

### B) Learner learning flow + CRUD touchpoints (4 minutes)

**What to show**
1. Browse courses (`courses` READ).
2. Enroll in a course (`course_enrollment` CREATE; `UNIQUE(user_id, course_id)` prevents duplicates).
3. Open a module/lesson (`modules`, `lessons` READ).
4. Open a challenge (`challenges`, `challenge_testcases` READ).
5. Submit code (`submissions` CREATE) and show status/score update if applicable.
6. Show progress update (`user_progress` CREATE/UPDATE) and XP (`user_xp` UPDATE).
7. Show leaderboard (`leaderboard` READ; derived from XP via joins in the app logic).

**Specific lines to say (verbatim-ready)**
**[Presenter Name] (speaking):**
First, we will browse the available courses. This is a READ from the `courses` table.

**[Presenter Name] (speaking):**
Now I will enroll in a course. This creates a row in `course_enrollment`, and the unique constraint prevents duplicate enrollments.

**[Presenter Name] (speaking):**
Next, I will open a module and lesson. These are READ operations from `modules` and `lessons`.

**[Presenter Name] (speaking):**
Now we will open a coding challenge and its test cases from `challenges` and `challenge_testcases`.

**[Presenter Name] (speaking):**
I will submit a solution. This creates a row in `submissions`, and the app records the status and score.

**[Presenter Name] (speaking):**
After submission, we update learning progress in `user_progress` and award XP in `user_xp`.

**[Presenter Name] (speaking):**
Finally, we will view the leaderboard. That is a READ from `leaderboard`, derived from XP so we can rank learners.

**[Presenter Name] (speaking):**
This entire learner journey is the transactional side of the system: frequent inserts and updates. And it becomes very valuable later for analytics.

### C) Subscription + payment (2 minutes)

**What to show**
1. Select plan (`subscription_plans` READ).
2. Activate subscription (`user_subscriptions` CREATE/UPDATE).
3. Record payment (`payments` CREATE) and show status.

**Specific lines to say (verbatim-ready)**
**[Presenter Name] (speaking):**
Now we will show monetization. Here are the subscription plans. This is a READ from `subscription_plans`.

**[Presenter Name] (speaking):**
When I activate a plan, it creates or updates a record in `user_subscriptions`.

**[Presenter Name] (speaking):**
And when we record a payment, it inserts into `payments` with a status we can track.

**[Presenter Name] (speaking):**
This demonstrates functionality beyond content CRUD. We have a full workflow including subscriptions and payments.

### D) Admin panel CRUD + moderation (2 minutes)

**What to show**
1. Admin login.
2. CRUD a course/module/lesson (CREATE, UPDATE, DELETE examples).
3. CRUD challenges/test cases or view submissions.
4. Show moderation review entry for a challenge (`moderation_reviews` CREATE).

**Specific lines to say (verbatim-ready)**
**[Presenter Name] (speaking):**
Next, we will switch to an admin account to demonstrate full content management.

**[Presenter Name] (speaking):**
Here we can create, update, and delete a course, module, or lesson. This maps directly to CRUD operations on `courses`, `modules`, and `lessons`.

**[Presenter Name] (speaking):**
Admins can also manage challenges and test cases, and review learner submissions as needed.

**[Presenter Name] (speaking):**
For governance, moderation actions are logged in `moderation_reviews` so approvals and rejections are traceable.

---

## 14:00-17:00 - Data Warehousing Concepts and Project Reflection

**[Presenter Name] (speaking):**
Kody is primarily an **OLTP** system: it supports frequent inserts and updates like enrollments, progress updates, submissions, and payments. Our current schema is normalized to support transactional integrity across these workflows.

### Where data warehousing fits (opportunities in our system)
**[Presenter Name] (speaking):**
Even though we are not a full data warehouse, Kody produces data that is ideal for analytics, such as:
- `submissions` for attempt history and outcomes,
- `course_enrollment` and `user_progress` for learning behavior over time,
- and `payments` plus `user_subscriptions` for monetization and retention.

### Suggested DW approach (reflection + improvement plan)
**[Presenter Name] (speaking):**
If we were to extend Kody for data warehousing, we would add:

1) **ETL / ELT pipeline**
- Extract from OLTP tables (submissions, enrollments, progress, payments)
- Transform into analytics-ready data (clean timestamps, standardized keys, derived metrics)
- Load into a reporting schema (example: `kody_analytics`)

2) **Star-schema / dimensional modeling (example design)**
- **Facts**
  - `fact_submissions` (measures: score, status, attempt_count)
  - `fact_learning_activity` (measures: completions, time-to-complete)
  - `fact_payments` (measures: amount, successful_payment_flag)
- **Dimensions**
  - `dim_user`, `dim_course`, `dim_module`, `dim_challenge`, `dim_time`

3) **OLAP vs. OLTP separation**
- Keep OLTP for the application
- Use OLAP tables/aggregates for dashboards (faster queries, fewer joins for reporting)

4) **Performance and physical design**
- Add indexes on common join/filter keys (like `user_id`, `course_id`, `challenge_id`, timestamps)
- Add pre-aggregated summaries or views for leaderboards and dashboards

### Gap summary
**[Presenter Name] (speaking):**
Right now, our project captures the necessary data, but we have not implemented an ETL process, a dedicated analytics schema, or OLAP-optimized aggregates. These are clear improvement areas at larger scale.

---

## 17:00-19:30 - Big Data and Analytics Concepts and Project Reflection

**[Presenter Name] (speaking):**
Kody is not a "big data" platform today, but big data and analytics principles apply as usage grows.

### Big data lens (5Vs) applied to Kody
**[Presenter Name] (speaking):**
- **Volume**: many submissions over time (especially with multiple attempts per learner).
- **Velocity**: frequent events (progress updates, submissions, notifications).
- **Variety**: structured relational data plus semi-structured content (lesson text, code submissions).
- **Veracity**: ensuring data quality with consistent statuses, keys, and timestamps.
- **Value**: actionable insights like drop-off points, challenge difficulty tuning, and retention.

### Analytics we can already support (using current OLTP data)
**[Presenter Name] (speaking):**
With the existing tables, we can already compute:
- completion rates per course/module (`user_progress`, `course_enrollment`)
- pass/fail distribution (`submissions.execution_status`)
- top performers and XP levels (`user_xp`, `leaderboard`)
- conversion and churn signals (`user_subscriptions`, `payments`)

### Reflection: what we would add to make it analytics-ready at scale
**[Presenter Name] (speaking):**
To be analytics-ready at scale, we would add:
1) **Event logging**: for example a `user_events` table for page views, clicks, time-on-lesson, and attempts.
2) **Scalability**: partition large fact-style tables by time, cache hot aggregates, and use async processing for scoring/analytics jobs.
3) **Dashboards**: instructor drop-off analysis, admin revenue trends, and learner improvement tracking.

### Gap summary
**[Presenter Name] (speaking):**
So today we capture the core data, but true big-data readiness would require richer event capture, dedicated analytics processing/storage, and reporting features optimized for scale.

---

## 19:30-20:00 - Wrap-up

**[Presenter Name] (speaking):**
To conclude: Kody delivers a working quasi-LMS with CRUD coverage across core entities, integrated workflows, and a strong foundation for data warehousing and analytics. If extended, we would add an analytics schema, ETL/ELT, and richer event tracking to produce dashboards for instructors and admins.

**[Presenter Name] (speaking):**
Thank you for tuning in to our presentation. As always, we are the Cooked group and I am the group's presenter: Mark Cyrus Macaraeg

