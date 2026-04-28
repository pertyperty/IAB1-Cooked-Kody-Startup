<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Profile', 'workspace-page', ['data-page' => 'profile']);
renderWorkspaceNav('profile', 'Profile and Account Management', 'Manage account details, role requests, credentials, and sensitive account controls in one clean page.');
renderWorkspaceIntro('A. Account and Authentication', 'Account Center', 'Profile, contributor requests, and security-sensitive actions are organized into a cleaner account center.');
renderWorkspaceAreasRail('profile');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Account Overview</h3>
                <div id="profile-summary">Loading profile...</div>

                <h3>Request History</h3>
                <div id="request-table" class="table-wrap"></div>
                <div id="request-history-cards" class="catalog-list"></div>

                <h3>Instructor Credentials</h3>
                <div id="credential-table" class="table-wrap"></div>
                <div id="credential-history-cards" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface">
                <form class="stack-form api-form" data-module="auth" data-action="request_contributor_role" data-roles="learner">
                    <h3>Apply for Contributor Role</h3>
                    <input type="hidden" name="requested_role" value="contributor">
                    <textarea name="notes" placeholder="Tell us why you are ready to contribute learning content or challenges."></textarea>
                    <button type="submit">Submit Role Request</button>
                </form>

                <form class="stack-form api-form" data-module="auth" data-action="submit_instructor_credentials">
                    <h3>Submit Instructor Credentials</h3>
                    <input name="credential_title" placeholder="Credential title" required>
                    <input name="file_url" placeholder="Credential file URL" required>
                    <button type="submit">Submit Credentials</button>
                </form>

                <details class="advanced-drawer">
                    <summary>Edit Account</summary>
                    <form class="stack-form api-form" data-module="auth" data-action="edit_account" data-critical-msg="Apply profile changes? Email and password edits are critical actions.">
                        <h3>Update Account Details</h3>
                        <input name="full_name" placeholder="New full name">
                        <input name="new_email" type="email" placeholder="New email">
                        <input name="new_password" type="password" placeholder="New password">
                        <input name="current_password" type="password" placeholder="Current password for sensitive changes">
                        <button type="submit">Update Account</button>
                    </form>
                </details>

                <details class="advanced-drawer">
                    <summary>Danger Zone</summary>
                    <form class="stack-form api-form" data-module="auth" data-action="archive_account" data-critical-msg="Archive your account now? All active sessions will be revoked.">
                        <h3>Archive Account</h3>
                        <input name="password" type="password" placeholder="Confirm password" required>
                        <button type="submit" class="danger">Archive Account</button>
                    </form>

                    <form class="stack-form api-form" data-module="auth" data-action="delete_account" data-critical-msg="Delete your account permanently? This cannot be undone.">
                        <h3>Delete Account</h3>
                        <input name="confirmation_phrase" placeholder="Type DELETE MY ACCOUNT" required>
                        <input name="password" type="password" placeholder="Confirm password" required>
                        <button type="submit" class="danger">Delete Account</button>
                    </form>
                </details>

                <article class="catalog-card">
                    <h3>Account Actions</h3>
                    <p>Edit details, request contributor access, submit instructor credentials, and manage sensitive account controls from this page.</p>
                </article>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
