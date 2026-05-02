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
<h2>Payment</h2>
<p class="notice">Simulate payment and subscription activation.</p>

<?php if ($message !== ''): ?>
	<p class="<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<?php if (!$selectedPlan): ?>
	<p>Please choose a plan from <a href="/kody/subscription.php">Subscription Page</a>.</p>
<?php else: ?>
	<h3>Selected Plan</h3>
	<ul>
		<li>Plan ID: <?php echo (int) $selectedPlan['plan_id']; ?></li>
		<li>Plan Name: <?php echo htmlspecialchars($selectedPlan['plan_name']); ?></li>
		<li>Price: <?php echo htmlspecialchars((string) $selectedPlan['price']); ?></li>
		<li>Billing Cycle: <?php echo htmlspecialchars($selectedPlan['billing_cycle']); ?></li>
	</ul>

	<form method="post" action="/kody/payment.php?plan_id=<?php echo (int) $selectedPlan['plan_id']; ?>">
		<input type="hidden" name="plan_id" value="<?php echo (int) $selectedPlan['plan_id']; ?>">

		<label for="payment_method">Payment Method</label><br>
		<select id="payment_method" name="payment_method" required>
			<option value="">Select payment method</option>
			<option value="gcash">GCash</option>
			<option value="maya">Maya</option>
			<option value="card">Card</option>
			<option value="bank_transfer">Bank Transfer</option>
		</select><br><br>

		<button type="submit">Pay and Activate</button>
	</form>
<?php endif; ?>

<?php if (!empty($result)): ?>
	<h3>Payment Result Summary</h3>
	<ul>
		<li>Payment ID: <?php echo (int) $result['payment_id']; ?></li>
		<li>Payment Method: <?php echo htmlspecialchars($result['payment_method']); ?></li>
		<li>Payment Status: <?php echo htmlspecialchars($result['payment_status']); ?></li>
		<li>Subscription ID: <?php echo (int) $result['subscription_id']; ?></li>
		<li>Subscription Status: <?php echo htmlspecialchars($result['subscription_status']); ?></li>
		<li>Plan: <?php echo htmlspecialchars($result['plan_name']); ?></li>
		<li>Amount: <?php echo htmlspecialchars((string) $result['plan_price']); ?></li>
		<li>Billing Cycle: <?php echo htmlspecialchars($result['billing_cycle']); ?></li>
		<li>Start Date: <?php echo htmlspecialchars($result['start_date']); ?></li>
		<li>End Date: <?php echo htmlspecialchars($result['end_date']); ?></li>
	</ul>
<?php endif; ?>

<p><a href="/kody/subscription.php">Back to Subscription</a></p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

