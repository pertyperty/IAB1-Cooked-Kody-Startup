<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$currentUser = getCurrentUser();
$userId = (int) $currentUser['user_id'];

$message = '';
$messageType = 'notice';
$result = [];

$selectedPlanId = (int) ($_GET['plan_id'] ?? $_POST['plan_id'] ?? 0);
$selectedPlan = $selectedPlanId > 0 ? getSubscriptionPlanById($selectedPlanId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$paymentMethod = trim($_POST['payment_method'] ?? '');

	if (!$selectedPlan) {
		$message = 'Please select a valid plan first.';
	} elseif ($paymentMethod === '') {
		$message = 'Please select a payment method.';
	} else {
		$subscriptionResult = upsertUserSubscription(
			$userId,
			(int) $selectedPlan['plan_id'],
			$selectedPlan['billing_cycle']
		);

		if (!$subscriptionResult['success']) {
			$message = 'Subscription update failed.';
		} else {
			$paymentResult = createPaymentRecord(
				$userId,
				(int) $subscriptionResult['subscription_id'],
				(float) $selectedPlan['price'],
				$paymentMethod,
				'completed'
			);

			if (!$paymentResult['success']) {
				$message = 'Payment record failed to save.';
			} else {
				$message = 'Payment completed and subscription updated successfully.';
				$messageType = 'success';
				$result = [
					'plan_name' => $selectedPlan['plan_name'],
					'plan_price' => $selectedPlan['price'],
					'billing_cycle' => $selectedPlan['billing_cycle'],
					'subscription_id' => $subscriptionResult['subscription_id'],
					'subscription_status' => $subscriptionResult['status'],
					'start_date' => $subscriptionResult['start_date'],
					'end_date' => $subscriptionResult['end_date'],
					'payment_id' => $paymentResult['payment_id'],
					'payment_method' => $paymentMethod,
					'payment_status' => 'completed',
				];
			}
		}
	}
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
	<h2>Payment Checkout</h2>
	<p>Confirm your subscription top-up with a payment method and activate it immediately.</p>
</div>

<?php if ($message !== ''): ?>
	<p class="<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<section class="split-layout">
	<div class="topup-panel">
		<div class="section-heading">
			<h3>Selected Plan</h3>
			<?php if ($selectedPlan): ?>
				<span class="badge success">Ready</span>
			<?php endif; ?>
		</div>

		<?php if (!$selectedPlan): ?>
			<p>Please choose a plan from <a href="/kody/subscription.php">Subscription Page</a>.</p>
		<?php else: ?>
			<div class="timeline mt-075">
				<div class="timeline-item"><strong>Plan ID:</strong> <?php echo (int) $selectedPlan['plan_id']; ?></div>
				<div class="timeline-item"><strong>Plan Name:</strong> <?php echo htmlspecialchars($selectedPlan['plan_name']); ?></div>
				<div class="timeline-item"><strong>Price:</strong> $<?php echo htmlspecialchars((string) $selectedPlan['price']); ?></div>
				<div class="timeline-item"><strong>Billing Cycle:</strong> <?php echo htmlspecialchars($selectedPlan['billing_cycle']); ?></div>
			</div>

			<div class="page-actions mt-1">
				<a class="button-link" href="/kody/subscription.php">Back to plans</a>
			</div>
		<?php endif; ?>
	</div>

	<div class="topup-panel">
		<div class="section-heading">
			<h3>Payment Method</h3>
			<span class="badge info">Checkout</span>
		</div>

		<?php if ($selectedPlan): ?>
			<form method="post" action="/kody/payment.php?plan_id=<?php echo (int) $selectedPlan['plan_id']; ?>" class="mt-075">
				<input type="hidden" name="plan_id" value="<?php echo (int) $selectedPlan['plan_id']; ?>">

				<label for="payment_method">Choose a method</label>
				<select id="payment_method" name="payment_method" required>
					<option value="">Select payment method</option>
					<option value="gcash">GCash</option>
					<option value="maya">Maya</option>
					<option value="card">Card</option>
					<option value="bank_transfer">Bank Transfer</option>
				</select>

				<div class="timeline mt-1">
					<div class="timeline-item">The subscription record is updated immediately after payment.</div>
					<div class="timeline-item">A completed payment row is written to the payments table.</div>
				</div>

				<div class="page-actions mt-1">
					<button type="submit" class="primary">Pay and Activate</button>
				</div>
			</form>
		<?php else: ?>
			<p class="meta">Choose a plan first to enable checkout.</p>
		<?php endif; ?>
	</div>
</section>

<?php if (!empty($result)): ?>
	<section class="mt-1">
		<div class="section-heading">
			<h3>Payment Result Summary</h3>
			<span class="badge success">Completed</span>
		</div>
		<div class="timeline mt-075">
			<div class="timeline-item"><strong>Payment ID:</strong> <?php echo (int) $result['payment_id']; ?></div>
			<div class="timeline-item"><strong>Method:</strong> <?php echo htmlspecialchars($result['payment_method']); ?></div>
			<div class="timeline-item"><strong>Status:</strong> <?php echo htmlspecialchars($result['payment_status']); ?></div>
			<div class="timeline-item"><strong>Subscription ID:</strong> <?php echo (int) $result['subscription_id']; ?></div>
			<div class="timeline-item"><strong>Subscription Status:</strong> <?php echo htmlspecialchars($result['subscription_status']); ?></div>
			<div class="timeline-item"><strong>Plan:</strong> <?php echo htmlspecialchars($result['plan_name']); ?> · $<?php echo htmlspecialchars((string) $result['plan_price']); ?></div>
			<div class="timeline-item"><strong>Period:</strong> <?php echo htmlspecialchars($result['start_date']); ?> to <?php echo htmlspecialchars($result['end_date']); ?></div>
		</div>
	</section>
<?php endif; ?>

<p class="mt-1"><a class="button-link" href="/kody/subscription.php">Back to Subscription</a></p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

