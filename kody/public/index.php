<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Authentication Gateway', 'auth-page', ['data-page' => 'login']);
renderPublicNav('login');
?>
<header class="shell auth-hero">
    <div>
        <p class="eyebrow">Authentication First</p>
        <h1>Start Kody from login or registration</h1>
        <p class="hero-copy">This page is the direct entry point. Sign in with a seed account or register a new learner or instructor account.</p>
    </div>

    <div class="hero-note lockout-card">
        <strong>Account Lockout Policy</strong>
        <p>3 failed attempts = 15 minutes, 6 = 1 hour, 9 = 24 hours.</p>
        <p>Admin accounts cannot use self-service account recovery.</p>
    </div>
</header>

<main class="shell auth-layout">
    <section class="surface intro-surface">
        <div class="surface-header">
            <div>
                <p class="eyebrow">Seed Accounts</p>
                <h2>Quick Role Testing</h2>
            </div>
            <p class="section-copy">Select a role to prefill the login form.</p>
        </div>
        <div class="seed-grid">
            <button type="button" class="seed-card" data-seed-email="learner@kody.local" data-seed-password="learner123"><strong>Learner</strong><span>learner@kody.local</span></button>
            <button type="button" class="seed-card" data-seed-email="contributor@kody.local" data-seed-password="contributor123"><strong>Contributor</strong><span>contributor@kody.local</span></button>
            <button type="button" class="seed-card" data-seed-email="instructor@kody.local" data-seed-password="instructor123"><strong>Instructor</strong><span>instructor@kody.local</span></button>
            <button type="button" class="seed-card" data-seed-email="moderator@kody.local" data-seed-password="moderator123"><strong>Moderator</strong><span>moderator@kody.local</span></button>
            <button type="button" class="seed-card" data-seed-email="admin@kody.local" data-seed-password="admin123"><strong>Administrator</strong><span>admin@kody.local</span></button>
        </div>
    </section>

    <section class="surface auth-surface">
        <div id="auth-status" class="status-banner info">Use login to continue, or register a new account.</div>

        <div class="catalog-grid">
            <form id="login-form" class="stack-form catalog-card">
                <h3>UC A01 User Login</h3>
                <input name="email" type="email" placeholder="Email" required>
                <input name="password" type="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>

            <form id="register-form" class="stack-form catalog-card">
                <h3>UC A03 Register Account</h3>
                <input name="full_name" placeholder="Full name" required>
                <input name="email" type="email" placeholder="Email" required>
                <input name="password" type="password" placeholder="Password" required>
                <select name="requested_role" id="register-role-select">
                    <option value="learner">Learner</option>
                    <option value="instructor">Instructor</option>
                </select>

                <div id="instructor-fields" class="context-panel hidden-block">
                    <p class="section-copy">Instructor applications require verification details on signup.</p>
                    <input name="credential_title" placeholder="Primary credential title">
                    <input name="file_url" placeholder="Credential file or portfolio URL">
                    <textarea name="expertise_summary" placeholder="Teaching background, expertise, and verification notes"></textarea>
                </div>

                <button type="submit">Register Account</button>
            </form>
        </div>

        <div class="inline-actions">
            <a class="button-link secondary-link" href="verify.php">Verify Email</a>
            <a class="button-link secondary-link" href="recover.php">Recover Account</a>
            <a class="button-link secondary-link" href="home.php">Go to Homepage</a>
        </div>
    </section>
</main>

<section class="shell response-console">
    <h2>Authentication Response Log</h2>
    <pre id="auth-response">Waiting for authentication actions...</pre>
</section>
<?php
renderFooter(['assets/js/auth.js']);
