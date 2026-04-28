<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Login', 'auth-page', ['data-page' => 'login']);
renderPublicNav('login');
?>
<header class="shell auth-hero">
    <div>
        <p class="eyebrow">Authentication</p>
        <h1>Login to Kody</h1>
        <p class="hero-copy">Enter your account credentials to continue to the website homepage and role-based sections.</p>
    </div>

    <div class="hero-note lockout-card">
        <strong>Account Lockout</strong>
        <p>3 failed attempts = 15 minutes, 6 = 1 hour, 9 = 24 hours.</p>
        <p>Use the seed-account cards below for quick role testing.</p>
    </div>
</header>
<?php renderAuthFlowRail('login'); ?>

<main class="shell auth-layout">
    <section class="surface intro-surface">
        <div class="surface-header">
            <div>
                <p class="eyebrow">Seed Accounts</p>
                <h2>Quick Role Testing</h2>
            </div>
            <p class="section-copy">Click a role to prefill the login form.</p>
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
        <div id="auth-status" class="status-banner info">Login to open the working website.</div>
        <form id="login-form" class="stack-form">
            <h3>UC A01 User Login</h3>
            <input name="email" type="email" placeholder="Email" required>
            <input name="password" type="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="inline-actions">
            <a class="button-link secondary-link" href="register.php">Need an account?</a>
            <a class="button-link secondary-link" href="recover.php">Recover account</a>
        </div>
    </section>
</main>

<section class="shell response-console">
    <h2>Authentication Response Log</h2>
    <pre id="auth-response">Waiting for login...</pre>
</section>
<?php
renderFooter(['assets/js/auth.js']);
