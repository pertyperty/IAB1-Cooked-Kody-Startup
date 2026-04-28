<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Homepage', 'workspace-page', ['data-page' => 'home']);
renderWorkspaceNav('home', 'Kody Homepage', 'Your logged-in homepage shows profile state, progress, notifications, and operational shortcuts in a cleaner dashboard.');
renderWorkspaceIntro('Homepage', 'Welcome Back', 'This is your main dashboard with quick context and direct workflow entry points.');
renderWorkspaceAreasRail('home');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Today Focus</h3>
                <div class="pill-row">
                    <span class="pill">Continue learning modules</span>
                    <span class="pill">Attempt challenge</span>
                    <span class="pill">Track rank movement</span>
                </div>
            </article>
            <article class="surface inset-surface">
                <h3>Quick Snapshot</h3>
                <p class="section-copy">Use metric cards and operational shortcuts below to navigate your most important tasks in one click.</p>
            </article>
        </div>

        <div id="overview-metrics" class="metrics-grid"></div>
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Profile Summary</h3>
                <div id="profile-summary">Loading profile...</div>
            </article>
            <article class="surface inset-surface">
                <h3>Role Coverage</h3>
                <div id="role-permissions">Loading role permissions...</div>
            </article>
        </div>
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>My Learning Snapshot</h3>
                <div id="learning-table" class="table-wrap"></div>
            </article>
            <article class="surface inset-surface">
                <h3>Notifications</h3>
                <div id="notification-list" class="list-wrap"></div>
            </article>
        </div>
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Connected Service Interfaces</h3>
                <div class="integration-grid">
                    <article class="integration-card"><strong>OAuth Sign-In</strong><p>Interface visible, local auth currently active.</p></article>
                    <article class="integration-card"><strong>Email Delivery</strong><p>Verification and recovery flows show simulated delivery state.</p></article>
                    <article class="integration-card"><strong>Cloud Asset Storage</strong><p>Credential and content URLs are shown as if hosted already.</p></article>
                    <article class="integration-card"><strong>Execution Engine</strong><p>Challenge evaluation UI behaves like a connected judge pipeline.</p></article>
                </div>
            </article>
        </div>

        <article class="surface inset-surface">
            <h3>Operational Shortcuts</h3>
            <p class="section-copy">Open the page for each workflow directly from the homepage.</p>
            <div id="home-shortcuts" class="home-shortcut-grid"></div>
        </article>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
