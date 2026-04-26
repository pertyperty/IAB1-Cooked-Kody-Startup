<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Account Recovery', 'auth-page', ['data-page' => 'recover']);
renderPublicNav('recover');
?>
<header class="shell auth-hero">
    <div>
        <p class="eyebrow">Authentication</p>
        <h1>Recover your account</h1>
        <p class="hero-copy">Request a recovery token and then reset the password with that token on the same page.</p>
    </div>
    <div class="hero-note">
        <strong>Restriction</strong>
        <p>Administrator accounts are excluded from self-service recovery in the current ruleset.</p>
    </div>
</header>

<main class="shell auth-layout">
    <section class="surface auth-surface">
        <div id="auth-status" class="status-banner info">Request a token first, then reset the password below.</div>
        <form id="recover-form" class="stack-form">
            <h3>UC A02 Recover Account</h3>
            <input name="email" type="email" placeholder="Account email" required>
            <button type="submit">Request Recovery Token</button>
        </form>

        <form id="reset-form" class="stack-form">
            <h3>Reset Password</h3>
            <input name="token" placeholder="Recovery token" required>
            <input name="new_password" type="password" placeholder="New password" required>
            <button type="submit">Reset Password</button>
        </form>
    </section>

    <section class="surface intro-surface">
        <div class="surface-header">
            <div>
                <p class="eyebrow">For Testing</p>
                <h2>Latest Recovery Token</h2>
            </div>
            <p class="section-copy">The most recent recovery token appears here when an eligible account requests one.</p>
        </div>
        <div id="token-board" class="token-board">Waiting for recovery request...</div>
    </section>
</main>

<section class="shell response-console">
    <h2>Authentication Response Log</h2>
    <pre id="auth-response">Waiting for recovery flow...</pre>
</section>
<?php
renderFooter(['assets/js/auth.js']);
