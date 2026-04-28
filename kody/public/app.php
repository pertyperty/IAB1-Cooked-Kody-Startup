<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Legacy App Entry', 'auth-page', ['data-page' => 'app']);
renderPublicNav('home');
?>
<header class="shell auth-hero">
	<div>
		<p class="eyebrow">Legacy Entry</p>
		<h1>This page has moved</h1>
		<p class="hero-copy">The old app entry now points to the new multi-page website flow.</p>
	</div>

	<div class="hero-note lockout-card">
		<strong>Where to go next</strong>
		<p>Use Login for returning users, Register for new users, and Homepage for authenticated workspace flows.</p>
	</div>
</header>
<?php renderAuthFlowRail('index'); ?>

<main class="shell auth-layout">
	<section class="surface auth-surface">
		<div class="status-banner info">Choose the destination page below.</div>
		<div class="inline-actions">
			<a class="button-link secondary-link" href="login.php">Login</a>
			<a class="button-link secondary-link" href="register.php">Register</a>
			<a class="button-link secondary-link" href="verify.php">Verify Email</a>
			<a class="button-link secondary-link" href="recover.php">Recover Account</a>
			<a class="button-link secondary-link" href="home.php">Homepage</a>
		</div>
	</section>
</main>
<?php
renderFooter();
