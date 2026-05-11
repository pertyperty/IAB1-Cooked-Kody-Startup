<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$currentUser = getCurrentUser();
$dashboardData = getUserDashboard((int) $currentUser['user_id']);
$user = $dashboardData['user'];
$enrollments = $dashboardData['enrollments'];
$notifications = getUserNotifications((int) $currentUser['user_id'], 5);
$unreadNotificationCount = getUnreadNotificationCount((int) $currentUser['user_id']);
$roleAssignments = getUserRoleAssignments((int) $currentUser['user_id']);
$instructorRequests = getInstructorRequestsByUser((int) $currentUser['user_id']);

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
  <h2>Dashboard</h2>
  <p>Track your progress, jump into challenges, and manage your learning path from one place.</p>
  <div class="page-actions mt-1">
    <a class="primary" href="/kody/course.php">Browse Courses</a>
    <a href="/kody/enroll.php">Enroll Now</a>
    <a href="/kody/submit_code.php">Submit Code</a>
    <a href="/kody/progress.php">View Progress</a>
    <a href="/kody/subscription.php">Subscription</a>
  </div>
</div>

<div class="card-grid">
  <div class="info-card">
    <div class="eyebrow">Learner</div>
    <div class="metric"><?php echo (int) ($user['level'] ?? 1); ?></div>
    <div class="card-meta">Level</div>
  </div>
  <div class="info-card">
    <div class="eyebrow">XP</div>
    <div class="metric"><?php echo (int) ($user['total_xp'] ?? 0); ?></div>
    <div class="card-meta">Total XP earned</div>
  </div>
  <div class="info-card">
    <div class="eyebrow">Enrollments</div>
    <div class="metric"><?php echo (int) count($enrollments); ?></div>
    <div class="card-meta">Active course connections</div>
  </div>
  <div class="info-card">
    <div class="eyebrow">Status</div>
    <div class="metric"><?php echo htmlspecialchars($user['account_status'] ?? ''); ?></div>
    <div class="card-meta">Account state</div>
  </div>
  <div class="info-card">
    <div class="eyebrow">Notifications</div>
    <div class="metric"><?php echo (int) $unreadNotificationCount; ?></div>
    <div class="card-meta">Unread items</div>
  </div>
</div>

<section class="split-layout mt-1">
  <div class="subtle-panel">
    <div class="section-heading">
      <h3>Profile snapshot</h3>
      <span class="badge info">User <?php echo (int) ($user['user_id'] ?? 0); ?></span>
    </div>
    <?php if ($user): ?>
      <div class="timeline mt-075">
        <div class="timeline-item"><strong>Name:</strong> <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></div>
        <div class="timeline-item"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></div>
        <div class="timeline-item"><strong>Joined:</strong> <?php echo htmlspecialchars($user['created_at']); ?></div>
      </div>
    <?php else: ?>
      <p class="notice">User record not found.</p>
    <?php endif; ?>
  </div>

  <div class="subtle-panel">
    <div class="section-heading">
      <h3>Quick start</h3>
      <span class="badge success">Start here</span>
    </div>
    <div class="timeline mt-075">
      <div class="timeline-item"><strong>1.</strong> Enroll in a course to unlock modules.</div>
      <div class="timeline-item"><strong>2.</strong> Open a lesson or challenge and submit code.</div>
      <div class="timeline-item"><strong>3.</strong> View XP, levels, and progress cards below.</div>
    </div>
  </div>
</section>

<section>
  <div class="section-heading">
    <h3>Enrolled Courses</h3>
    <a class="button-link" href="/kody/enroll.php">Manage Enrollment</a>
  </div>
  <?php if (count($enrollments) > 0): ?>
    <div class="card-grid mt-1">
      <?php foreach ($enrollments as $enrollment): ?>
        <article class="content-card">
          <div class="section-heading align-start">
            <div>
              <div class="panel-eyebrow">Course</div>
              <h4 class="mb-025"><?php echo htmlspecialchars($enrollment['title']); ?></h4>
            </div>
            <span class="badge <?php echo ((int) $enrollment['is_archived'] === 1) ? 'warn' : 'success'; ?>">
              <?php echo ((int) $enrollment['is_archived'] === 1) ? 'Archived' : 'Active'; ?>
            </span>
          </div>
          <p class="meta">Difficulty: <?php echo htmlspecialchars($enrollment['difficulty'] ?? ''); ?></p>
          <p class="meta">Enrolled at: <?php echo htmlspecialchars($enrollment['enrolled_at']); ?></p>
          <p class="meta">Status: <?php echo htmlspecialchars($enrollment['completion_status']); ?></p>
          <a class="card-button primary" href="/kody/course.php?course_id=<?php echo (int) $enrollment['course_id']; ?>">Open Course</a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No enrolled courses yet.</p>
    <a class="card-button primary" href="/kody/enroll.php">Start by enrolling in your first course</a>
  <?php endif; ?>
</section>

<section class="mt-1">
  <div class="section-heading">
    <h3>Recent Notifications</h3>
    <a class="button-link" href="/kody/notifications.php">Open inbox</a>
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
          <div class="card-meta mt-04">Created: <?php echo htmlspecialchars($notification['created_at']); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No notifications yet.</p>
  <?php endif; ?>
</section>

<section class="mt-1 split-layout split-2">
  <div class="subtle-panel">
    <div class="section-heading">
      <h3>Role History</h3>
      <span class="badge info"><?php echo (int) count($roleAssignments); ?> entries</span>
    </div>
    <?php if (count($roleAssignments) > 0): ?>
      <div class="timeline mt-075">
        <?php foreach ($roleAssignments as $assignment): ?>
          <div class="timeline-item">
            <strong><?php echo htmlspecialchars($assignment['role_name']); ?></strong>
            <div class="card-meta mt-035">Assigned: <?php echo htmlspecialchars($assignment['assigned_at']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No role history available.</p>
    <?php endif; ?>
  </div>

  <div class="subtle-panel">
    <div class="section-heading">
      <h3>Instructor Requests</h3>
      <a class="button-link" href="/kody/instructor_request.php">Request access</a>
    </div>
    <?php if (count($instructorRequests) > 0): ?>
      <div class="timeline mt-075">
        <?php foreach ($instructorRequests as $request): ?>
          <div class="timeline-item">
            <strong><?php echo htmlspecialchars($request['status']); ?></strong>
            <div class="card-meta mt-035">Requested: <?php echo htmlspecialchars($request['requested_at']); ?></div>
            <?php if (!empty($request['reviewed_at'])): ?>
              <div class="card-meta mt-035">Reviewed: <?php echo htmlspecialchars($request['reviewed_at']); ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No instructor requests yet.</p>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

