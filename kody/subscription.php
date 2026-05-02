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
<h2>Subscription</h2>
<p class="notice">Choose a subscription plan below.</p>

<h3>Your Current Subscription</h3>
<?php if ($currentSubscription): ?>
	<ul>
		<li>Subscription ID: <?php echo (int) $currentSubscription['subscription_id']; ?></li>
		<li>Plan: <?php echo htmlspecialchars($currentSubscription['plan_name'] ?? 'Unknown'); ?></li>
		<li>Price: <?php echo htmlspecialchars((string) $currentSubscription['price']); ?></li>
		<li>Billing Cycle: <?php echo htmlspecialchars($currentSubscription['billing_cycle'] ?? ''); ?></li>
		<li>Status: <?php echo htmlspecialchars($currentSubscription['status']); ?></li>
		<li>Start Date: <?php echo htmlspecialchars($currentSubscription['start_date']); ?></li>
		<li>End Date: <?php echo htmlspecialchars($currentSubscription['end_date']); ?></li>
	</ul>
<?php else: ?>
	<p>No subscription record yet.</p>
<?php endif; ?>

<h3>Available Plans</h3>
<?php if (count($plans) > 0): ?>
	<table>
		<thead>
			<tr>
				<th>Plan ID</th>
				<th>Plan Name</th>
				<th>Price</th>
				<th>Billing Cycle</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($plans as $plan): ?>
				<tr>
					<td><?php echo (int) $plan['plan_id']; ?></td>
					<td><?php echo htmlspecialchars($plan['plan_name']); ?></td>
					<td><?php echo htmlspecialchars((string) $plan['price']); ?></td>
					<td><?php echo htmlspecialchars($plan['billing_cycle']); ?></td>
					<td>
						<a href="/kody/payment.php?plan_id=<?php echo (int) $plan['plan_id']; ?>">Select Plan</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php else: ?>
	<p>No plans found. Add seed data to `subscription_plans` first.</p>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

