# Database Field Usage Analysis Report

## Scope

- Schema source: `database.sql`.
- Code source: PHP files under `kody/`, `kody/admin/`, `kody/actions/`, `kody/includes/`.
- Goal: identify used fields, underused fields, and uncovered tables.

---

## Executive Summary

The codebase uses the majority of schema fields for live workflows (auth, courses, enrollment, submissions, progress, XP, subscriptions, payments). The remaining gaps are primarily feature-completeness gaps (for example, test-case execution flow and notification usage in user-facing flows).

### Highlights

- Core workflow tables are actively used.
- Admin CRUD now includes `user_subscriptions` and `moderation_reviews` modules.
- Some fields remain reserved for future features (for example, OAuth/avatar support).

---

## Table Status Matrix

| Table | Status | Notes |
| ----- | ------ | ----- |
| users | Active | Core auth/profile fields in use; `google_id` and `profile_picture` are currently reserved. |
| roles | Active | Used in role assignment and authorization checks. |
| user_roles | Active | Role links used; `assigned_at` mostly audit-oriented. |
| instructor_requests | Partial | CRUD-ready; timestamp fields are minimally surfaced in UX. |
| courses | Active | Fully used in browsing/enrollment/dashboard. |
| course_enrollment | Active | Used for enrollment and completion state. |
| modules | Active | Used in course structure and progress selectors. |
| lessons | Active | Used in course module rendering and progress selectors. |
| challenges | Active | Used in challenge browsing/submission flow. |
| challenge_testcases | Partial | Schema exists; runtime validation flow is not fully integrated. |
| submissions | Active | Insert and reporting path in place. |
| user_progress | Active | Create/update/query paths in place. |
| user_xp | Active | XP and level updates/reads are active. |
| leaderboard | Partial | Rankings are computed dynamically from XP joins. |
| moderation_reviews | Partial | Admin CRUD exposed; user-facing moderation workflow is limited. |
| subscription_plans | Active | Used in subscription and checkout screens. |
| user_subscriptions | Active | Used in lifecycle updates and status display. |
| payments | Active | Used in payment recording and admin metrics. |
| notifications | Partial | CRUD-accessible; user-facing notification center is limited. |

---

## Fields With Limited Practical Usage

The following fields are present and valid in schema, but not deeply integrated into current end-user flows:

| Table | Field | Current State |
| ----- | ----- | ------------- |
| users | google_id | Reserved for OAuth/SSO integration. |
| users | profile_picture | Reserved for profile/avatar upload feature. |
| user_roles | assigned_at | Audit timestamp; limited UI surfacing. |
| instructor_requests | requested_at | Stored but minimally surfaced. |
| instructor_requests | reviewed_at | Stored but minimally surfaced. |
| leaderboard | rank_position | Rank is computed on-demand in current UX. |

---

## Implementation Notes for Underused and API-Dependent Items

The following notes clarify what is already implemented, what is partial, and what depends on external services.

| Item | Current Implementation | Dependency | Implementation Status | Next Step |
| ---- | ---------------------- | ---------- | --------------------- | --------- |
| users.google_id | Column is present and constrained as unique. Login flow currently uses email and password only. | OAuth provider | Partial | Add OAuth callback flow that maps provider user ID to this field. |
| users.profile_picture | Column is present. UI currently uses no upload/storage pipeline. | File storage strategy | Partial | Add profile image upload endpoint and render image in profile/header. |
| user_roles.assigned_at | Timestamp is auto-populated by DB default on insert. Not exposed in runtime UI. | None | Partial | Surface in admin role history and assignment views. |
| instructor_requests.requested_at | Timestamp is auto-populated when request rows are created. Limited surfacing. | None | Partial | Display in instructor request list and detail views. |
| instructor_requests.reviewed_at | Field exists for review completion timestamp. Limited surfacing. | None | Partial | Set and display consistently when request status changes. |
| leaderboard.rank_position | Field exists but runtime ranking is computed from user_xp join. | None | Partial | Choose computed-only model or add periodic rank persistence job. |
| challenge_testcases.input_data and expected_output | Table exists and can be managed through admin CRUD. Runtime submit flow does not execute against testcase set. | Judge0 or internal runner | Partial | Evaluate submissions against testcases and map pass/fail per testcase. |
| submissions.execution_status and score | Values are currently set by local app workflow, not sandbox-execution feedback. | Judge0 | Partial | Populate status and score from Judge0 result payloads. |
| payments.payment_method, payment_status, paid_at | Local payment flow records these fields without provider webhook lifecycle. | Xendit | Partial | Add payment intent creation and webhook reconciliation to set final status/timestamp. |
| notifications.message, is_read, created_at | Table and admin CRUD are available, but user-facing inbox/consume flow is limited. | None | Partial | Add learner/instructor notification list and mark-as-read actions. |
| moderation_reviews.decision and review_notes | Table and admin CRUD are available. End-user moderation workflow is limited. | None | Partial | Add moderator review queue with approve/reject actions tied to challenge status. |

---

## File Interaction Summary

### Primary business-logic hub

- `kody/includes/functions.php`
  - Centralized query and helper functions.
  - CRUD definitions and table mapping.
  - Subscription/payment/progress helpers.

### Core workflow pages

- `kody/login.php`, `kody/register.php`, `kody/logout.php`
- `kody/dashboard.php`, `kody/course.php`, `kody/enroll.php`, `kody/progress.php`
- `kody/submit_code.php`, `kody/process_submission.php`
- `kody/subscription.php`, `kody/payment.php`, `kody/leaderboard.php`

### Admin and action pipeline

- `kody/admin/*.php`
- `kody/includes/admin_crud.php`
- `kody/actions/create.php`, `kody/actions/update.php`, `kody/actions/delete.php`

---

## Current Gaps and Recommendations

### High-priority implementation gaps

1. Integrate `challenge_testcases` into submission evaluation path.
2. Decide persistent-vs-computed ranking strategy for `leaderboard`.
3. Expand user-facing notification consumption patterns if needed.

### Medium-priority enhancement opportunities

1. Expose audit timestamps (`assigned_at`, `requested_at`, `reviewed_at`) in targeted admin/user views.
2. Implement OAuth and avatar features if product roadmap requires them.

### Low-priority housekeeping

1. Align documentation wording with actual runtime behavior each release.
2. Keep CRUD map and admin navigation synchronized when adding new modules.

---

## Coverage Snapshot

| Category | Coverage Snapshot |
| -------- | ----------------- |
| Authentication | Strong |
| Course and lesson flow | Strong |
| Submission and progress | Strong (with testcase integration pending) |
| XP and leaderboard | Strong (computed-rank model) |
| Subscription and payment | Strong |
| Moderation and notifications | CRUD-ready, workflow depth can improve |

---

## Conclusion

Database integration is robust for current product workflows. Remaining items are mostly roadmap-level enhancements rather than structural defects. The schema is positioned for future expansion, while existing runtime paths are stable for core LMS usage.
