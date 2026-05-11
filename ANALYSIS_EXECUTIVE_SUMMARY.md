# Database Analysis Executive Summary

## Analysis Overview

This report summarizes schema-to-code usage across the repository and identifies unused fields, partially integrated tables, and recommended next actions.

### Scope

- Schema source: database.sql
- Code source: PHP files under kody, kody/admin, kody/actions, and kody/includes
- Tables reviewed: 19
- Focus: runtime workflows, admin CRUD exposure, and field-level usage

## Key Findings

### What is working well

- Core LMS workflows are strongly implemented for auth, courses, enrollment, progress, submissions, subscriptions, and payments.
- Most high-value operational tables are actively used.
- The admin layer exposes CRUD modules for major schema entities.

### Main gaps (Updated - Most now resolved)

- ✅ **notifications**: Now fully implemented with user-facing inbox (notifications.php), header badges, and mark-read functionality.
- ✅ **moderation_reviews**: Now exposed in admin CRUD for moderator workflow.
- ✅ **instructor_requests**: Now fully implemented with user submission form (instructor_request.php), admin CRUD, and timestamp tracking.
- ✅ **leaderboard**: Rank positions now persisted via updateLeaderboardRank() helper function.
- Remaining gap: challenge_testcases is present but not deeply wired into submission evaluation (awaits Judge0 integration).
- Roadmap items pending external APIs: Judge0 for code execution scoring, Xendit for payment webhook reconciliation.

## Coverage Snapshot

| Category | Status | Notes |
| -------- | ------ | ----- |
| Authentication | Strong | users, roles, user_roles are active |
| Course Delivery | Strong | courses, modules, lessons are active |
| Submissions and Progress | Strong with gap | challenge_testcases integration pending |
| Gamification | Strong | User XP tracking + persisted leaderboard ranking via rank_position |
| Subscription and Payments | Strong | Plan and payment lifecycle is active |
| Moderation and Notifications | Strong | ✅ Full notification inbox + admin moderation interface ready |

## Fields Implementation Status (Phase 6 Complete)

| Table | Field | Current State | Status |
| ----- | ----- | ------------- | ------ |
| users | google_id | Reserved for future OAuth | Reserved - remove if OAuth not planned |
| users | profile_picture | Reserved for avatar upload | Reserved - remove if avatar not planned |
| user_roles | assigned_at | ✅ Now surfaced in admin role CRUD | Complete |
| instructor_requests | requested_at | ✅ Now displayed in instructor_request.php and admin CRUD | Complete |
| instructor_requests | reviewed_at | ✅ Now displayed in instructor_request.php and admin CRUD | Complete |
| leaderboard | rank_position | ✅ Now persisted via updateLeaderboardRank() | Complete |
| notifications | * (5 fields) | ✅ Full implementation in notifications.php, header.php, dashboard.php | Complete |
| moderation_reviews | * (6 fields) | ✅ Admin CRUD interface exposed and ready | Exposed |

## Table-Level Status Matrix

| Table Group | Status | Notes |
| ----------- | ------ | ----- |
| Core workflow tables | Active | used directly by user-facing pages |
| Admin-only workflow tables | Partial | available via generic CRUD wrappers |
| Future-facing fields | Partial | valid schema placeholders for roadmap |

## Updated Priority Recommendations

### Priority 1: Complete external API integrations (High Value)

1. Integrate Judge0 for challenge_testcases evaluation in process_submission.php.
2. Integrate Xendit webhooks for payment status reconciliation (payments.paid_at, user_subscriptions.status).
3. Wire Judge0 execution results into submissions.score and submissions.execution_status.

### Priority 2: Expand moderation workflow (Medium Value)

1. Implement moderator queue and approval/rejection actions in moderation_reviews table.
2. Surface moderation reviews in admin interface for content moderation workflows.
3. Add reviewer assignment and response messaging capabilities.

### Priority 3: Optional OAuth and profile features (Low Priority)

1. Implement OAuth integration for users.google_id if requested in product roadmap.
2. Implement profile picture upload/storage for users.profile_picture if requested.
3. Remove unused fields if they are not planned within 2 release cycles.

### Priority 4: Release hygiene (Ongoing)

1. Re-run markdown diagnostics on generated reports before handoff.
2. Keep report tables in compact style with lint-safe pipe spacing.
3. Add a short changelog entry when schema usage status changes.

## Impact Assessment

### Current risk

- Functional risk: low for existing core workflows.
- Maintenance risk: medium due to partially integrated tables and reserved fields.
- Data-model risk: low, because schema remains structurally coherent.

### Technical debt profile

- Mostly roadmap debt, not runtime breakage.
- Highest-value debt payoff is testcase-driven evaluation integration.

## Recommended Next Actions (Phase 6 Complete - Roadmap Updated)

✅ **Completed in Phase 6:**

- Implemented notifications table with full user-facing inbox (notifications.php, header badges, mark-read actions).
- Implemented instructor_requests with user submission form and admin CRUD review interface.
- Persisted leaderboard rankings via updateLeaderboardRank() helper function.
- Exposed moderation_reviews admin CRUD interface ready for moderator workflows.
- Updated all timestamp fields to display in admin views and user-facing pages.

**Next Actions (Roadmap):**

1. Integrate Judge0 and map execution outputs into submissions.execution_status and submissions.score.
2. Integrate Xendit webhooks and reconcile payments.paid_at and user_subscriptions.status via webhook events.
3. Expand moderation queue workflow and implement moderator approval/rejection actions.
4. Implement OAuth integration for users.google_id if product roadmap requires it.
5. Implement profile picture upload for users.profile_picture if product roadmap requires it.

## Conclusion

The codebase is operationally strong for core LMS behavior and supports current product needs. Remaining findings are primarily integration-depth and roadmap alignment items rather than critical defects.
