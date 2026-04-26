<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Homepage', 'landing-page', ['data-page' => 'landing']);
renderPublicNav('home');
?>
<header class="shell landing-hero">
    <div class="hero-copy-block">
        <p class="eyebrow">K:GPLPCMS Working Website</p>
        <h1>Kody turns the SRS into a browsable learning platform, creator system, and governance portal.</h1>
        <p class="hero-copy">
            This version is structured like a real website: separate pages for authentication, homepage, profile,
            learning, creator work, rewards, finance, and governance. External services remain simulated, but their
            product-facing interfaces are represented in the website.
        </p>
        <div class="inline-actions">
            <a class="button-link" href="login.php">Login</a>
            <a class="button-link secondary-link" href="register.php">Register</a>
        </div>
    </div>

    <div class="surface hero-panel">
        <p class="eyebrow">Platform Snapshot</p>
        <h2>What is already working</h2>
        <div class="pill-row">
            <span class="pill">Login and registration</span>
            <span class="pill">Email verification flow</span>
            <span class="pill">Lockout enforcement</span>
            <span class="pill">Role-based navigation</span>
            <span class="pill">Learning and challenge browsing</span>
            <span class="pill">Creator and governance operations</span>
        </div>
        <div class="integration-grid">
            <article class="integration-card">
                <strong>OAuth Interface</strong>
                <p>UI shown as connected, but local login remains the active path.</p>
            </article>
            <article class="integration-card">
                <strong>Email Provider Interface</strong>
                <p>Verification and recovery email states are displayed through the local testing mailbox.</p>
            </article>
            <article class="integration-card">
                <strong>Judge Interface</strong>
                <p>Submission and evaluation screens are live, while execution is still simulated locally.</p>
            </article>
            <article class="integration-card">
                <strong>Payment Interface</strong>
                <p>Token purchase and payout UI are working with local payment records and provider-style output.</p>
            </article>
        </div>
    </div>
</header>

<main class="shell workspace-stack">
    <section class="surface">
        <div class="surface-header">
            <div>
                <p class="eyebrow">Website Areas</p>
                <h2>Multi-Page Structure</h2>
            </div>
            <p class="section-copy">The site is no longer limited to a single auth screen and one large dashboard.</p>
        </div>
        <div class="catalog-grid">
            <article class="catalog-card">
                <h3>Authentication Pages</h3>
                <p>Dedicated pages for login, registration, email verification, and account recovery.</p>
            </article>
            <article class="catalog-card">
                <h3>Homepage</h3>
                <p>Role-aware landing area after login with profile, metrics, notifications, and connected service panels.</p>
            </article>
            <article class="catalog-card">
                <h3>Learning</h3>
                <p>Courses, modules, challenges, leaderboard, FAQ, and code-submission paths.</p>
            </article>
            <article class="catalog-card">
                <h3>Creator and Governance</h3>
                <p>Separate pages for course/module/challenge management, rewards, finance, and moderation work.</p>
            </article>
        </div>
    </section>

    <section class="surface">
        <div class="surface-header">
            <div>
                <p class="eyebrow">Role Access</p>
                <h2>Seed Accounts</h2>
            </div>
            <p class="section-copy">Quick entry accounts for testing the inheritance model.</p>
        </div>
        <div class="seed-grid">
            <button type="button" class="seed-card" data-seed-email="learner@kody.local" data-seed-password="learner123">
                <strong>Learner</strong>
                <span>Accesses learning, profile, and finance basics</span>
            </button>
            <button type="button" class="seed-card" data-seed-email="contributor@kody.local" data-seed-password="contributor123">
                <strong>Contributor</strong>
                <span>Adds challenge and creator capabilities</span>
            </button>
            <button type="button" class="seed-card" data-seed-email="instructor@kody.local" data-seed-password="instructor123">
                <strong>Instructor</strong>
                <span>Gets contributor plus course/module authoring</span>
            </button>
            <button type="button" class="seed-card" data-seed-email="moderator@kody.local" data-seed-password="moderator123">
                <strong>Moderator</strong>
                <span>Reviews queues, reports, users, and weekly challenge flows</span>
            </button>
            <button type="button" class="seed-card" data-seed-email="admin@kody.local" data-seed-password="admin123">
                <strong>Administrator</strong>
                <span>Has all use cases except account recovery</span>
            </button>
        </div>
    </section>
</main>
<?php
renderFooter(['assets/js/auth.js']);
