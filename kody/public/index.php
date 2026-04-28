<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Authentication Gateway', 'auth-page', ['data-page' => 'index']);
renderPublicNav('home');
?>
<header class="shell auth-hero">
    <div>
        <p class="eyebrow">Public Gateway</p>
        <h1>Welcome to Kody</h1>
        <p class="hero-copy">Start from the gateway, then continue into a dedicated login, registration, verification, recovery, or workspace page. This keeps each task in its own clean interface.</p>
    </div>

    <div class="hero-note lockout-card">
        <strong>System Audit Snapshot</strong>
        <p>The page structure is now split by workflow, but backend hardening is still needed around CSRF, open CORS policy, and the monolithic API router.</p>
        <p>Admin accounts cannot use self-service account recovery, and login lockout still applies at 3, 6, and 9 failed attempts.</p>
    </div>
</header>
<?php renderAuthFlowRail('index'); ?>

<main class="shell auth-layout">
    <section class="surface auth-surface">
        <div class="surface-header">
            <div>
                <p class="eyebrow">Choose A Page</p>
                <h2>Separate Web Interfaces</h2>
            </div>
            <p class="section-copy">Each public workflow now routes to its own page instead of being bundled into one oversized entry form.</p>
        </div>
        <div id="auth-status" class="status-banner info">Choose a destination page below to continue.</div>
        <div class="gateway-grid">
            <a class="gateway-card" href="login.php">
                <span class="rail-kicker">Returning User</span>
                <strong>Login Page</strong>
                <p>Use the dedicated sign-in screen with seed-account shortcuts and lockout messaging.</p>
            </a>
            <a class="gateway-card" href="register.php">
                <span class="rail-kicker">New Account</span>
                <strong>Registration Page</strong>
                <p>Create a learner account or start an instructor-track application with separate verification inputs.</p>
            </a>
            <a class="gateway-card" href="verify.php">
                <span class="rail-kicker">Activation</span>
                <strong>Verification Page</strong>
                <p>Activate an account with the generated token in its own focused verification flow.</p>
            </a>
            <a class="gateway-card" href="recover.php">
                <span class="rail-kicker">Support</span>
                <strong>Recovery Page</strong>
                <p>Request a reset token and complete password recovery in a dedicated support interface.</p>
            </a>
            <a class="gateway-card" href="home.php">
                <span class="rail-kicker">Authenticated</span>
                <strong>Workspace Homepage</strong>
                <p>Enter the logged-in product and branch into learning, creator, finance, rewards, or governance areas.</p>
            </a>
        </div>
    </section>

    <section class="surface intro-surface">
        <div class="surface-header">
            <div>
                <p class="eyebrow">Seed Accounts</p>
                <h2>Quick Role Testing</h2>
            </div>
            <p class="section-copy">Pick a role here and the system will carry the credentials into the dedicated login page for you.</p>
        </div>
        <div class="seed-grid">
            <button type="button" class="seed-card" data-seed-email="learner@kody.local" data-seed-password="learner123"><strong>Learner</strong><span>learner@kody.local</span></button>
            <button type="button" class="seed-card" data-seed-email="contributor@kody.local" data-seed-password="contributor123"><strong>Contributor</strong><span>contributor@kody.local</span></button>
            <button type="button" class="seed-card" data-seed-email="instructor@kody.local" data-seed-password="instructor123"><strong>Instructor</strong><span>instructor@kody.local</span></button>
            <button type="button" class="seed-card" data-seed-email="moderator@kody.local" data-seed-password="moderator123"><strong>Moderator</strong><span>moderator@kody.local</span></button>
            <button type="button" class="seed-card" data-seed-email="admin@kody.local" data-seed-password="admin123"><strong>Administrator</strong><span>admin@kody.local</span></button>
        </div>
        <div class="inline-actions">
            <a class="button-link secondary-link" href="app.php">Open Legacy Entry</a>
            <a class="button-link secondary-link" href="login.php">Go to Login</a>
        </div>
    </section>
    <section class="surface intro-surface">
        <div class="surface-header">
            <div>
                <p class="eyebrow">Audit Priorities</p>
                <h2>Next Hardening Areas</h2>
            </div>
            <p class="section-copy">These are the cross-cutting items that matter most after the interface cleanup.</p>
        </div>
        <div class="catalog-grid">
            <article class="catalog-card">
                <h3>Security Controls</h3>
                <p>Write endpoints still need CSRF protection, and the API currently allows a permissive wildcard CORS policy.</p>
            </article>
            <article class="catalog-card">
                <h3>Backend Structure</h3>
                <p>The API router is doing too much in one file, which makes audits and targeted maintenance slower than they should be.</p>
            </article>
            <article class="catalog-card">
                <h3>Session Strategy</h3>
                <p>Auth state is still stored client-side for page bootstrapping, so true server-side page guards remain an open architectural task.</p>
            </article>
            <article class="catalog-card">
                <h3>Verification Depth</h3>
                <p>The site covers the use cases well, but scenario-based regression checks are still missing for the role matrix and high-risk flows.</p>
            </article>
        </div>
    </section>
</main>

<section class="shell response-console">
    <h2>Gateway Notes</h2>
    <pre id="auth-response">The gateway now routes users into dedicated public pages instead of embedding login and registration forms directly here.</pre>
</section>
<?php
renderFooter(['assets/js/auth.js']);
