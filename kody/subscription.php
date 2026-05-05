<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$currentUser = getCurrentUser();
$userId = (int) $currentUser['user_id'];

$plans = getSubscriptionPlans();
$currentSubscription = getUserLatestSubscription($userId);

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
	<h2>Subscription Top-Up</h2>
	<p>Pick a plan card to activate or renew your subscription like a checkout flow.</p>
	<div class="page-actions mt-1">
		<a class="primary" href="/kody/payment.php">Open Payment</a>
		<a href="/kody/dashboard.php">Back to Dashboard</a>
	</div>
</div>

<section class="split-layout">
	<div class="topup-panel">
		<div class="section-heading">
			<h3>Current Subscription</h3>
			<?php if ($currentSubscription): ?>
				<span class="badge success"><?php echo htmlspecialchars($currentSubscription['status']); ?></span>
			<?php endif; ?>
		</div>
		<?php if ($currentSubscription): ?>
			<div class="timeline mt-075">
				<div class="timeline-item"><strong>Subscription #</strong> <?php echo (int) $currentSubscription['subscription_id']; ?></div>
				<div class="timeline-item"><strong>Plan:</strong> <?php echo htmlspecialchars($currentSubscription['plan_name'] ?? 'Unknown'); ?></div>
				<div class="timeline-item"><strong>Price:</strong> <?php echo htmlspecialchars((string) $currentSubscription['price']); ?></div>
				<div class="timeline-item"><strong>Billing:</strong> <?php echo htmlspecialchars($currentSubscription['billing_cycle'] ?? ''); ?></div>
				<div class="timeline-item"><strong>Period:</strong> <?php echo htmlspecialchars($currentSubscription['start_date']); ?> to <?php echo htmlspecialchars($currentSubscription['end_date']); ?></div>
			</div>
		<?php else: ?>
			<p>No subscription record yet.</p>
			<p class="meta">Choose a plan below to create your first active subscription.</p>
		<?php endif; ?>
	</div>

	<div class="topup-panel">
		<div class="section-heading">
			<h3>Checkout Steps</h3>
			<span class="badge info">Top-up flow</span>
		</div>
		<div class="timeline mt-075">
			<div class="timeline-item"><strong>1.</strong> Choose a plan card.</div>
			<div class="timeline-item"><strong>2.</strong> Confirm payment method on the payment page.</div>
			<div class="timeline-item"><strong>3.</strong> Subscription and payment records update automatically.</div>
		</div>
	</div>
</section>

<section class="mt-1">
	<div class="section-heading">
		<h3>Available Plans</h3>
		<span class="badge info"><?php echo (int) count($plans); ?> plans</span>
	</div>
	<?php if (count($plans) > 0): ?>
		<div class="topup-grid mt-1">
			<?php foreach ($plans as $plan): ?>
				<article class="topup-card">
					<div class="section-heading align-start">
						<div>
							<div class="panel-eyebrow">Plan #<?php echo (int) $plan['plan_id']; ?></div>
							<h4 class="mb-025"><?php echo htmlspecialchars($plan['plan_name']); ?></h4>
						</div>
						<span class="badge dark"><?php echo htmlspecialchars($plan['billing_cycle']); ?></span>
					</div>
					<div class="price">$<?php echo htmlspecialchars((string) $plan['price']); ?></div>
					<div class="card-meta">Billed <?php echo htmlspecialchars($plan['billing_cycle']); ?></div>
					<div class="page-actions mt-auto">
						<a class="primary" href="/kody/payment.php?plan_id=<?php echo (int) $plan['plan_id']; ?>">Top up</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else: ?>
		<p>No plans found. Add seed data to `subscription_plans` first.</p>
	<?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

