# Database Table to File Interaction Matrix

## Scope

- Schema source: database.sql
- Code source: kody, kody/admin, kody/actions, kody/includes
- Goal: map table usage to files and operation types

## Matrix

| Table | Runtime Status | Primary Files | Operation Types | Notes |
| ----- | -------------- | ------------- | --------------- | ----- |
| users | Active | login.php, register.php, dashboard.php, leaderboard.php, includes/functions.php | SELECT, INSERT | google_id and profile_picture currently reserved |
| roles | Active | login.php, register.php, includes/functions.php | SELECT | Used for role lookup and assignment |
| user_roles | Active | login.php, register.php, includes/functions.php | SELECT, INSERT | assigned_at mainly audit metadata |
| instructor_requests | Partial | admin/instructor_requests_crud.php, includes/functions.php | CRUD via generic layer | Workflow usage is limited |
| courses | Active | course.php, enroll.php, dashboard.php, includes/functions.php | SELECT | Fully integrated |
| course_enrollment | Active | enroll.php, dashboard.php, includes/functions.php | SELECT, INSERT | Completion status used |
| modules | Active | course.php, progress.php, includes/functions.php | SELECT | Fully integrated |
| lessons | Active | course.php, progress.php, includes/functions.php | SELECT | Fully integrated |
| challenges | Active | submit_code.php, process_submission.php, progress.php, admin/index.php, includes/functions.php | SELECT | Fully integrated |
| challenge_testcases | Partial | admin/testcases_crud.php, includes/functions.php | CRUD via generic layer | Not wired to submission evaluation path |
| submissions | Active | process_submission.php, admin/index.php, includes/functions.php | INSERT, SELECT | Fully integrated |
| user_progress | Active | progress.php, process_submission.php, includes/functions.php | SELECT, INSERT, UPDATE | Fully integrated |
| user_xp | Active | register.php, dashboard.php, leaderboard.php, process_submission.php, includes/functions.php | SELECT, INSERT, UPDATE | Fully integrated |
| leaderboard | Partial | admin/leaderboard_crud.php, includes/functions.php | CRUD via generic layer | Runtime rankings computed from user_xp |
| moderation_reviews | Partial | admin/moderation_reviews_crud.php, includes/functions.php | CRUD via generic layer | User-facing moderation flow limited |
| subscription_plans | Active | subscription.php, payment.php, register.php, includes/functions.php | SELECT, INSERT | Fully integrated |
| user_subscriptions | Active | subscription.php, payment.php, register.php, includes/functions.php | SELECT, INSERT, UPDATE | Fully integrated |
| payments | Active | payment.php, admin/index.php, includes/functions.php | INSERT, SELECT | Fully integrated |
| notifications | Partial | admin/notifications_crud.php, includes/functions.php | CRUD via generic layer | User-facing notification center limited |

## File-Centric View

### User-facing pages

- login.php: users, roles, user_roles
- register.php: users, roles, user_roles, user_xp, subscription_plans, user_subscriptions
- dashboard.php: users, user_xp, course_enrollment, courses
- course.php: courses, modules, lessons, users
- enroll.php: courses, course_enrollment
- progress.php: user_progress, courses, modules, lessons, challenges
- submit_code.php: challenges
- process_submission.php: submissions, user_progress, user_xp, challenges
- leaderboard.php: users, user_xp
- subscription.php: subscription_plans, user_subscriptions
- payment.php: subscription_plans, user_subscriptions, payments

### Admin and generic CRUD

- admin/index.php: users, courses, submissions, payments, challenges
- admin/*_crud.php: table-specific wrappers for generic CRUD renderer
- includes/admin_crud.php: runtime CRUD engine for mapped tables
- includes/functions.php: table definitions and module-to-table mapping

### Action handlers

- actions/create.php: INSERT by mapped table
- actions/update.php: UPDATE by mapped table
- actions/delete.php: DELETE by mapped table

## Observations

1. Core LMS workflows are strongly integrated across schema and runtime paths.
2. Partial tables are generally available through admin CRUD but not deeply wired into end-user flows.
3. leaderboard table is structurally present while runtime ranking is computed on demand.
4. challenge_testcases is the most important workflow gap because evaluation currently does not depend on stored testcases.

## Priority Follow-ups

1. Integrate challenge_testcases into process_submission.php evaluation logic.
2. Decide whether leaderboard should stay computed-only or become persisted.
3. Expand notifications and moderation_reviews into user-facing workflows when roadmap requires.
