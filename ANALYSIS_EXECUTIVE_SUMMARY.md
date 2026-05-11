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

### Main gaps

- Some schema artifacts are reserved or partially integrated rather than fully used in runtime workflows.
- challenge_testcases is present but not deeply wired into submission evaluation.
- notifications and moderation_reviews are available in admin CRUD but limited in user-facing workflow depth.
- leaderboard rankings are computed dynamically from user_xp rather than persisted via leaderboard.rank_position.
- Some partial behaviors are intentional until external integrations are wired (for example, Judge0 for execution scoring and Xendit for payment-status webhook lifecycle).

## Coverage Snapshot

| Category | Status | Notes |
| -------- | ------ | ----- |
| Authentication | Strong | users, roles, user_roles are active |
| Course Delivery | Strong | courses, modules, lessons are active |
| Submissions and Progress | Strong with gap | challenge_testcases integration pending |
| Gamification | Strong with model choice | runtime ranking uses user_xp join |
| Subscription and Payments | Strong | plan and payment lifecycle is active |
| Moderation and Notifications | Partial | CRUD-ready, limited user-facing workflows |

## Fields with Limited Practical Usage

| Table | Field | Current State | Suggested Direction |
| ----- | ----- | ------------- | ------------------- |
| users | google_id | Reserved for future OAuth | Keep if OAuth planned; otherwise remove |
| users | profile_picture | Reserved for avatar upload | Keep if avatar roadmap exists; otherwise remove |
| user_roles | assigned_at | Mostly audit metadata | Surface in admin role-history views |
| instructor_requests | requested_at | Stored but lightly surfaced | Expose in request timeline |
| instructor_requests | reviewed_at | Stored but lightly surfaced | Expose in review timeline |
| leaderboard | rank_position | Not used in current runtime model | Persist rankings or remove redundancy |

## Table-Level Status Matrix

| Table Group | Status | Notes |
| ----------- | ------ | ----- |
| Core workflow tables | Active | used directly by user-facing pages |
| Admin-only workflow tables | Partial | available via generic CRUD wrappers |
| Future-facing fields | Partial | valid schema placeholders for roadmap |

## Priority Recommendations

### Priority 1: workflow integrity

1. Integrate challenge_testcases into process_submission.php validation path.
2. Decide leaderboard strategy: computed rankings only or persisted ranking table.
3. Confirm moderation and notifications roadmap and implement end-user flow if required.

### Priority 2: schema clarity

1. Remove roadmap-only fields that are not planned for near-term implementation.
2. Keep audit timestamps but surface them in appropriate admin views.
3. Keep CRUD map and admin navigation synchronized when adding modules.

### Priority 3: release hygiene

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

## Recommended Next Actions

1. Implement testcase-driven validation in submission processing.
2. Add lightweight user-facing notification reads if notifications are retained.
3. Expand moderation workflow beyond admin CRUD wrappers when product scope requires it.
4. Integrate Judge0 and map execution outputs into submissions.execution_status and submissions.score.
5. Integrate Xendit and reconcile payments.payment_status and payments.paid_at via webhooks.

## Conclusion

The codebase is operationally strong for core LMS behavior and supports current product needs. Remaining findings are primarily integration-depth and roadmap alignment items rather than critical defects.
