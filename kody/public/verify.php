<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Email Verification', 'auth-page', ['data-page' => 'verify']);
renderPublicNav('verify');
?>
<header class="shell auth-hero">
    <div>
        <p class="eyebrow">Authentication</p>
        <h1>Verify your email</h1>
        <p class="hero-copy">Paste the generated verification token here to activate the account and unlock login.</p>
    </div>
    <div class="hero-note">
        <strong>Local website flow</strong>
        <p>The verification token is shown through the local testing mailbox instead of a real email provider.</p>
    </div>
</header>

<main class="shell auth-layout">
    <section class="surface auth-surface">
        <div id="auth-status" class="status-banner info">Submit a verification token to activate your account.</div>
        <form id="verify-form" class="stack-form">
            <h3>UC A04 Verify Email</h3>
            <input name="token" placeholder="Verification token" required>
            <button type="submit">Verify Email</button>
        </form>
        <div class="inline-actions">
            <a class="button-link secondary-link" href="register.php">Need a new account?</a>
            <a class="button-link secondary-link" href="login.php">Back to login</a>
        </div>
    </section>

    <section class="surface intro-surface">
        <div class="surface-header">
            <div>
                <p class="eyebrow">For Testing</p>
                <h2>Latest Verification Token</h2>
            </div>
            <p class="section-copy">The most recent registration token is shown here when available.</p>
        </div>
        <div id="token-board" class="token-board">Waiting for a verification token...</div>
    </section>
</main>

<section class="shell response-console">
    <h2>Authentication Response Log</h2>
    <pre id="auth-response">Waiting for verification...</pre>
</section>
<?php
renderFooter(['assets/js/auth.js']);
