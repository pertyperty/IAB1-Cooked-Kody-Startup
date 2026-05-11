<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$currentUser = getCurrentUser();
$userId = (int) $currentUser['user_id'];
$message = '';
$messageType = 'notice';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notificationId = (int) ($_POST['notification_id'] ?? 0);
    if ($notificationId > 0) {
        $ok = markNotificationAsRead($userId, $notificationId);
        $message = $ok ? 'Notification marked as read.' : 'Unable to update notification.';
        $messageType = $ok ? 'success' : 'notice';
    }

    if (isset($_POST['mark_all']) && $_POST['mark_all'] === '1') {
        $ok = markAllNotificationsAsRead($userId);
        $message = $ok ? 'All notifications marked as read.' : 'Unable to update notifications.';
        $messageType = $ok ? 'success' : 'notice';
    }
}

$notifications = getUserNotifications($userId, 50);

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
  <h2>Notifications</h2>
  <p>Review messages, course events, and account updates without leaving the app.</p>
</div>

<?php if ($message !== ''): ?>
  <p class="<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<section>
  <div class="section-heading">
    <h3>Your Inbox</h3>
    <form method="post" class="page-actions">
      <button type="submit" name="mark_all" value="1" class="primary">Mark all read</button>
    </form>
  </div>

  <?php if (count($notifications) > 0): ?>
    <div class="timeline mt-1">
      <?php foreach ($notifications as $notification): ?>
        <div class="timeline-item">
          <div class="section-heading align-start">
            <div>
              <div class="panel-eyebrow">Notification #<?php echo (int) $notification['notification_id']; ?></div>
              <strong><?php echo htmlspecialchars($notification['message']); ?></strong>
            </div>
            <span class="badge <?php echo ((int) $notification['is_read'] === 1) ? 'dark' : 'success'; ?>">
              <?php echo ((int) $notification['is_read'] === 1) ? 'Read' : 'Unread'; ?>
            </span>
          </div>
          <div class="card-meta mt-035">Created: <?php echo htmlspecialchars($notification['created_at']); ?></div>
          <?php if ((int) $notification['is_read'] === 0): ?>
            <form method="post" class="page-actions mt-075">
              <input type="hidden" name="notification_id" value="<?php echo (int) $notification['notification_id']; ?>">
              <button type="submit">Mark read</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No notifications found.</p>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
