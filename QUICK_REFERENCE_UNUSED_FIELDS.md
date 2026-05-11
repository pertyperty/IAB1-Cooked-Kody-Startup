# Database Field Usage Quick Reference

## Unused Fields Summary

| Table | Field | Type | Status | Files Using Table |
| ----- | ----- | ---- | ------ | ----------------- |
| users | google_id | VARCHAR(50) | Unused | login, register, dashboard, leaderboard |
| users | profile_picture | VARCHAR(255) | Unused | login, register, dashboard, leaderboard |
| user_roles | assigned_at | DATETIME | Unused in runtime UX | login, register |
| instructor_requests | requested_at | DATETIME | Unused in runtime UX | admin CRUD only |
| instructor_requests | reviewed_at | DATETIME | Unused in runtime UX | admin CRUD only |
| leaderboard | rank_position | INT | Unused | leaderboard table not queried |

## Underused or Unused Tables

| Table | Fields | Status | Recommendation |
| ----- | ------ | ------ | -------------- |
| moderation_reviews | 6 | Not queried by workflows | Remove or implement moderation flow |
| notifications | 5 | Not queried by workflows | Remove or implement notification flow |
| challenge_testcases | 4 | Partially integrated | Wire into submission validation |
| leaderboard | 3 | Bypassed at runtime | Keep computed model or persist rankings |

## Practical Implementation Status Notes

| Area | What Exists Now | Why It Is Partial | Integration Needed |
| ---- | --------------- | ----------------- | ------------------ |
| Code execution validation | submissions and challenge_testcases schema, plus submit/process pages | Runtime does not evaluate solutions against stored testcases using sandbox output | Judge0 (or internal execution sandbox) |
| Payments lifecycle | payments, user_subscriptions, and local checkout flow | Current flow is app-local and not provider-webhook reconciled | Xendit API and webhook events |
| OAuth account link | users.google_id column | Login/register currently email/password only | OAuth provider callback mapping |
| Profile images | users.profile_picture column | No upload/storage/display pipeline yet | Storage service plus upload endpoint |
| Notifications | notifications table and admin CRUD | No full user-facing inbox flow yet | No external API required |
| Moderation reviews | moderation_reviews table and admin CRUD | No full moderator queue-to-decision flow in user runtime | No external API required |

## Table Query Frequency Snapshot

| Table | Used In | Query Count | Status |
| ----- | ------- | ----------- | ------ |
| users | 5+ files | 10+ queries | Heavily used |
| courses | 4+ files | 8+ queries | Heavily used |
| user_xp | 4+ files | 6+ queries | Heavily used |
| course_enrollment | 3+ files | 5+ queries | Used |
| challenges | 4+ files | 5+ queries | Used |
| user_progress | 3+ files | 4+ queries | Used |
| submissions | 3+ files | 3+ queries | Used |
| modules | 3+ files | 3+ queries | Used |
| lessons | 3+ files | 3+ queries | Used |
| subscription_plans | 3+ files | 3+ queries | Used |
| user_subscriptions | 3+ files | 4+ queries | Used |
| payments | 2+ files | 2+ queries | Used |
| roles | 3+ files | 3+ queries | Used |
| user_roles | 3+ files | 3+ queries | Used |
| instructor_requests | admin only | 1 query | Minimal |
| moderation_reviews | none | 0 queries | Unused |
| notifications | none | 0 queries | Unused |
| challenge_testcases | none | 0 direct queries | Unused at runtime |
| leaderboard | none | 0 direct queries | Bypassed |

## File-Level Access Summary

### Core application pages

- login.php: users, roles, user_roles (SELECT)
- register.php: users, user_roles, roles, user_xp, subscription_plans, user_subscriptions (INSERT and SELECT)
- dashboard.php: users, user_xp, course_enrollment, courses (SELECT)
- course.php: courses, modules, lessons, users (SELECT)
- enroll.php: courses, course_enrollment (SELECT and INSERT)
- submit_code.php: challenges (SELECT)
- process_submission.php: submissions, user_progress, user_xp, challenges (INSERT and UPDATE)
- progress.php: user_progress, courses, modules, lessons, challenges (SELECT, INSERT, UPDATE)
- payment.php: subscription_plans, user_subscriptions, payments (SELECT, UPDATE, INSERT)
- subscription.php: subscription_plans, user_subscriptions (SELECT)
- leaderboard.php: users, user_xp (SELECT)

### Admin and action pipeline

- admin/index.php: users, courses, submissions, payments, challenges (SELECT)
- admin/*_crud.php: all mapped tables through generic CRUD layer
- actions/create.php: INSERT by module
- actions/update.php: UPDATE by module
- actions/delete.php: DELETE by module

### Include layer

- includes/functions.php: main query and workflow logic hub
- includes/auth.php: session and role checks (no direct schema writes)
- includes/db.php: connection bootstrap

## Unused Field Breakdown

### users.google_id

- Location: database.sql line 10
- Type: VARCHAR(50) UNIQUE
- Current state: never set or checked
- Expected use: OAuth integration
- Suggested action: remove unless OAuth is planned

### users.profile_picture

- Location: database.sql line 12
- Type: VARCHAR(255)
- Current state: never set or rendered
- Expected use: profile avatar
- Suggested action: remove or implement uploads

### user_roles.assigned_at

- Location: database.sql line 39
- Type: DATETIME DEFAULT CURRENT_TIMESTAMP
- Current state: stored but not surfaced
- Expected use: role assignment audit trail
- Suggested action: keep and expose in admin history

### instructor_requests.requested_at

- Location: database.sql line 51
- Type: DATETIME DEFAULT CURRENT_TIMESTAMP
- Current state: stored but not surfaced
- Expected use: request timeline
- Suggested action: display in admin instructor request views

### instructor_requests.reviewed_at

- Location: database.sql line 52
- Type: DATETIME
- Current state: stored but not surfaced
- Expected use: review timeline
- Suggested action: display in admin instructor request views

### leaderboard.rank_position

- Location: database.sql line 181
- Type: INT
- Current state: never written or read
- Expected use: persisted ranking position
- Current reality: ranking computed from user_xp
- Suggested action: either populate leaderboard table or remove it

## Impact Assessment

### Risk level: medium

- Core workflows remain correct and stable.
- Unused fields do not break existing features.
- Unused tables increase maintenance burden.
- Missing testcase integration is a quality gap.

### Technical debt: medium

- 6 underused or unused fields across 3 tables.
- 2 tables not used by runtime workflows.
- 1 table with schema-only integration.

## Remediation Priority

### Phase 1: high

1. Implement challenge_testcases in submission validation or remove table.
2. Implement moderation_reviews workflow or remove table.
3. Implement notifications workflow or remove table.

### Phase 2: medium

1. Implement OAuth or remove users.google_id.
2. Implement avatar upload or remove users.profile_picture.
3. Persist ranking into leaderboard or remove redundant table.

### Phase 3: low

1. Surface user_roles.assigned_at in role audit views.
2. Surface instructor_requests.requested_at in request timeline.
3. Surface instructor_requests.reviewed_at in review timeline.
