<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Registration', 'auth-page', ['data-page' => 'register']);
renderPublicNav('register');
?>
<header class="shell auth-hero">
    <div>
        <p class="eyebrow">Authentication</p>
        <h1>Create a Kody account</h1>
        <p class="hero-copy">Register as a learner or apply directly as an instructor. Learners can request contributor access later from their profile page.</p>
    </div>
    <div class="hero-note">
        <strong>Verification required</strong>
        <p>The site will generate an email-verification token after registration so the account can be activated locally.</p>
    </div>
</header>
<?php renderAuthFlowRail('register'); ?>

<main class="shell auth-layout">
    <section class="surface auth-surface">
        <div id="auth-status" class="status-banner info">Fill in your details to create an account.</div>
        <form id="register-form" class="stack-form">
            <h3>Create Your Account</h3>
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
        <div class="inline-actions">
            <a class="button-link secondary-link" href="verify.php">Already have a token?</a>
            <a class="button-link secondary-link" href="login.php">Back to login</a>
        </div>
    </section>

    <section class="surface intro-surface">
        <div class="surface-header">
            <div>
                <p class="eyebrow">Registration Notes</p>
                <h2>Account Setup Guidance</h2>
            </div>
            <p class="section-copy">Choose learner for immediate study access, or instructor if you want your credentials reviewed after registration.</p>
        </div>
        <div class="catalog-card">
            <h3>Learner path</h3>
            <p>Creates a normal learner account. Contributor access can be requested later inside the profile page.</p>
        </div>
        <div class="catalog-card">
            <h3>Instructor path</h3>
            <p>Creates the account, stores your credential submission, and opens an instructor verification request for moderation.</p>
        </div>
    </section>
</main>

<section class="shell response-console developer-console">
    <h2>For Testing: Verification Token Board</h2>
    <div id="token-board" class="token-board">Waiting for registration...</div>
    <h2>For Testing: Authentication Response Log</h2>
    <pre id="auth-response">Waiting for registration...</pre>
</section>
<?php
renderFooter(['assets/js/auth.js']);
