# Database Field Usage Quick Reference

## Unused Fields Summary

| Table | Field | Type | Status | Files Using Table |
| ----- | ----- | ---- | ------ | ----------------- |
| users | google_id | VARCHAR(50) | Unused | login, register, dashboard, leaderboard |
| users | profile_picture | VARCHAR(255) | Unused | login, register, dashboard, leaderboard |
| user_roles | assigned_at | DATETIME | ✅ Implemented | login, register, admin roles CRUD |
| instructor_requests | requested_at | DATETIME | ✅ Implemented | instructor_request.php, admin CRUD, functions.php |
| instructor_requests | reviewed_at | DATETIME | ✅ Implemented | instructor_request.php, admin CRUD, functions.php |
| leaderboard | rank_position | INT | ✅ Implemented | leaderboard.php, functions.php (updateLeaderboardRank) |
| notifications | * | 5 fields | ✅ Implemented | notifications.php, header.php, dashboard.php, admin CRUD |
| moderation_reviews | * | 6 fields | ✅ Exposed | admin moderation_reviews CRUD, functions.php |

## Underused or Unused Tables

| Table | Fields | Status | Recommendation |
| ----- | ------ | ------ | -------------- |
| moderation_reviews | 6 | ✅ Exposed in admin CRUD | Ready for moderation workflow implementation |
| notifications | 5 | ✅ Full user-facing implementation | Active in notifications.php, header, dashboard |
| challenge_testcases | 4 | Partially integrated | Wire into submission validation (requires Judge0) |
| leaderboard | 3 | ✅ Rank position persisted | Populated via updateLeaderboardRank() in functions.php |

## Practical Implementation Status Notes

| Area | What Exists Now | Status | Integration Needed |
| ---- | --------------- | ------ | ------------------ |
| Code execution validation | submissions and challenge_testcases schema, plus submit/process pages | Partial - schema ready but runtime does not evaluate solutions | Judge0 (or internal execution sandbox) |
| Payments lifecycle | payments, user_subscriptions, and local checkout flow | Partial - app-local flow works but not provider-reconciled | Xendit API and webhook events |
| OAuth account link | users.google_id column | Unused - login/register currently email/password only | OAuth provider callback mapping |
| Profile images | users.profile_picture column | Unused - no upload/storage/display pipeline yet | Storage service plus upload endpoint |
| Notifications | notifications table and admin CRUD + notifications.php | ✅ Complete - full user-facing inbox with read/unread tracking | Production ready |
| Moderation reviews | moderation_reviews table, admin CRUD, functions.php | ✅ Exposed - admin interface ready for moderator workflow | Ready for moderator action implementation |
| Instructor requests | instructor_requests table, instructor_request.php, admin CRUD | ✅ Complete - full learner submission and admin review workflow | Production ready |
| Leaderboard ranking | leaderboard table with rank_position, functions.php helper | ✅ Complete - ranks now persisted via updateLeaderboardRank() | Production ready |
| User roles audit trail | user_roles.assigned_at timestamp | ✅ Complete - displayed in admin roles CRUD | Visible in admin UI |

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
| notifications | 3+ files | 4+ queries | ✅ Now active in notifications.php, header, dashboard |
| instructor_requests | 2+ files | 3+ queries | ✅ Now active in instructor_request.php, admin CRUD |
| moderation_reviews | admin + functions | 2+ queries | ✅ Now exposed in admin CRUD |
| leaderboard | 2+ files | 2+ queries | ✅ Now populated via rank_position updates |
| challenge_testcases | none | 0 direct queries | Awaiting Judge0 integration |
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
