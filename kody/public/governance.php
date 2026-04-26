<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Governance', 'workspace-page', ['data-page' => 'governance']);
renderWorkspaceNav('governance', 'Governance and Administration', 'Moderators and administrators use this page for reports, reviews, user management, presets, and FAQ lifecycle operations.');
renderWorkspaceIntro('G. Administration and Governance', 'Governance Center', 'Moderation and administration are separated from learning and authoring pages.');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>User Accounts</h3>
                <div id="users-table" class="table-wrap"></div>

                <h3>Challenge Review Queue</h3>
                <div id="challenge-review-table" class="table-wrap"></div>

                <h3>Contributor Requests</h3>
                <div id="requests-review-table" class="table-wrap"></div>
                <div id="contributor-request-cards" class="catalog-list"></div>

                <h3>Instructor Credentials Queue</h3>
                <div id="credentials-review-table" class="table-wrap"></div>
                <div id="credential-review-cards" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface">
                <h3>Content Reports</h3>
                <div id="reports-table" class="table-wrap"></div>

                <h3>System Reports</h3>
                <div id="system-reports-table" class="table-wrap"></div>
            </article>

            <article class="surface inset-surface">
                <form class="stack-form api-form" data-module="challenge" data-action="review">
                    <h3>UC C05 Review Code Challenge</h3>
                    <input name="challenge_id" type="number" min="1" placeholder="Challenge ID" required>
                    <select name="review_status">
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                    <textarea name="review_notes" placeholder="Review notes"></textarea>
                    <button type="submit">Apply Challenge Decision</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="moderate_content">
                    <h3>UC G01 Moderate Content</h3>
                    <input name="report_id" type="number" min="1" placeholder="Report ID">
                    <input name="target_type" placeholder="Target type" required>
                    <input name="target_id" type="number" min="1" placeholder="Target ID" required>
                    <select name="action_type">
                        <option value="approve">approve</option>
                        <option value="archive">archive</option>
                        <option value="remove">remove</option>
                        <option value="reject">reject</option>
                        <option value="suspend">suspend</option>
                        <option value="reinstate">reinstate</option>
                    </select>
                    <textarea name="notes" placeholder="Moderation notes"></textarea>
                    <button type="submit">Moderate</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="update_request">
                    <h3>UC G03 Approve Contributor Requests</h3>
                    <input name="request_id" type="number" min="1" placeholder="Request ID" required>
                    <select name="status">
                        <option>Approved</option>
                        <option>Rejected</option>
                    </select>
                    <button type="submit">Apply Request Decision</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="verify_instructor_credentials">
                    <h3>UC A06 Verify Instructor Credentials</h3>
                    <input name="credential_id" type="number" min="1" placeholder="Credential ID" required>
                    <select name="verification_status">
                        <option>Accepted</option>
                        <option>Rejected</option>
                    </select>
                    <button type="submit">Verify Credential</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="suspend_user" data-critical-msg="Suspend this user account now?">
                    <h3>UC G04 Suspend User Account</h3>
                    <input name="user_id" type="number" min="1" placeholder="User ID" required>
                    <textarea name="notes" placeholder="Reason"></textarea>
                    <button type="submit" class="danger">Suspend User</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="reinstate_user">
                    <h3>UC G05 Reinstate User Account</h3>
                    <input name="user_id" type="number" min="1" placeholder="User ID" required>
                    <button type="submit">Reinstate User</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="update_user_account" data-roles="administrator">
                    <h3>UC G07 Update User Account</h3>
                    <input name="user_id" type="number" min="1" placeholder="User ID" required>
                    <input name="full_name" placeholder="New full name">
                    <select name="primary_role">
                        <option value="">No role change</option>
                        <option value="learner">learner</option>
                        <option value="contributor">contributor</option>
                        <option value="instructor">instructor</option>
                        <option value="moderator">moderator</option>
                        <option value="administrator">administrator</option>
                    </select>
                    <select name="status">
                        <option value="">No status change</option>
                        <option value="Active">Active</option>
                        <option value="Archived">Archived</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                    <button type="submit">Update User</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="create_game_preset" data-roles="administrator">
                    <h3>UC G08 Create Game Preset</h3>
                    <input name="preset_name" placeholder="Preset name" required>
                    <textarea name="rules_json" placeholder='{"mode":"speed"}'></textarea>
                    <textarea name="rewards_json" placeholder='{"xp":50,"kb":5}'></textarea>
                    <button type="submit">Create Preset</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="update_game_preset" data-roles="administrator">
                    <h3>UC G09 Update Game Preset</h3>
                    <input name="preset_id" type="number" min="1" placeholder="Preset ID" required>
                    <input name="preset_name" placeholder="New preset name">
                    <textarea name="rules_json" placeholder="New rules JSON"></textarea>
                    <textarea name="rewards_json" placeholder="New rewards JSON"></textarea>
                    <button type="submit">Update Preset</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="delete_game_preset" data-roles="administrator" data-critical-msg="Delete this preset?">
                    <h3>UC G10 Delete Game Preset</h3>
                    <input name="preset_id" type="number" min="1" placeholder="Preset ID" required>
                    <button type="submit" class="danger">Delete Preset</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="create_faq" data-roles="administrator">
                    <h3>UC G11 Create FAQ Entry</h3>
                    <input name="question" placeholder="Question" required>
                    <textarea name="answer" placeholder="Answer" required></textarea>
                    <button type="submit">Create FAQ</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="update_faq" data-roles="administrator">
                    <h3>UC G12 Update FAQ Entry</h3>
                    <input name="faq_id" type="number" min="1" placeholder="FAQ ID" required>
                    <input name="question" placeholder="New question">
                    <textarea name="answer" placeholder="New answer"></textarea>
                    <button type="submit">Update FAQ</button>
                </form>

                <form class="stack-form api-form" data-module="admin" data-action="delete_faq" data-roles="administrator" data-critical-msg="Delete this FAQ entry permanently?">
                    <h3>UC G13 Delete FAQ Entry</h3>
                    <input name="faq_id" type="number" min="1" placeholder="FAQ ID" required>
                    <button type="submit" class="danger">Delete FAQ</button>
                </form>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
