<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$currentUser = getCurrentUser();
$userId = (int) $currentUser['user_id'];
$message = '';
$messageType = 'notice';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestMessage = trim($_POST['request_message'] ?? '');
    if ($requestMessage === '') {
        $message = 'Please enter a request message.';
    } else {
        $result = submitInstructorRequest($userId, $requestMessage);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'notice';
    }
}

$requests = getInstructorRequestsByUser($userId);

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
  <h2>Instructor Request</h2>
  <p>Submit a role request for review. Timestamps are tracked automatically when the request is created and reviewed.</p>
</div>

<?php if ($message !== ''): ?>
  <p class="<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<section class="split-layout split-2">
  <div class="subtle-panel">
    <div class="section-heading">
      <h3>Submit Request</h3>
      <span class="badge info">Role upgrade</span>
    </div>
    <form method="post" class="mt-075">
      <label for="request_message">Message</label>
      <textarea id="request_message" name="request_message" rows="8" placeholder="Explain why you should be approved as an instructor" required></textarea>
      <div class="page-actions mt-1">
        <button type="submit" class="primary">Send request</button>
      </div>
    </form>
  </div>

  <div class="subtle-panel">
    <div class="section-heading">
      <h3>Your Requests</h3>
      <span class="badge success"><?php echo (int) count($requests); ?> total</span>
    </div>
    <?php if (count($requests) > 0): ?>
      <div class="timeline mt-075">
        <?php foreach ($requests as $request): ?>
          <div class="timeline-item">
            <strong><?php echo htmlspecialchars($request['status']); ?></strong>
            <div class="card-meta mt-035">Requested: <?php echo htmlspecialchars($request['requested_at']); ?></div>
            <?php if (!empty($request['reviewed_at'])): ?>
              <div class="card-meta mt-035">Reviewed: <?php echo htmlspecialchars($request['reviewed_at']); ?></div>
            <?php endif; ?>
            <div class="card-meta mt-035"><?php echo htmlspecialchars($request['request_message']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No requests submitted yet.</p>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
