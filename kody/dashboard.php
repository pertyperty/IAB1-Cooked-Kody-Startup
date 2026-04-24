<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$currentUser = getCurrentUser();
$dashboardData = getUserDashboard((int) $currentUser['user_id']);
$user = $dashboardData['user'];
$enrollments = $dashboardData['enrollments'];

require_once __DIR__ . '/includes/header.php';
?>
<h2>Dashboard</h2>
<p class="notice">Welcome, <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>.</p>
<p>
  <a href="/kody/enroll.php">Enroll in a Course</a> |
  <a href="/kody/progress.php">View Progress</a> |
  <a href="/kody/submit_code.php">Submit Code</a>
</p>

<h3>User Information</h3>
<?php if ($user): ?>
  <ul>
    <li>User ID: <?php echo (int) $user['user_id']; ?></li>
    <li>Email: <?php echo htmlspecialchars($user['email']); ?></li>
    <li>Status: <?php echo htmlspecialchars($user['account_status']); ?></li>
    <li>Joined At: <?php echo htmlspecialchars($user['created_at']); ?></li>
  </ul>
<?php else: ?>
  <p class="notice">User record not found.</p>
<?php endif; ?>

<h3>XP Summary</h3>
<?php if ($user): ?>
  <ul>
    <li>Total XP: <?php echo (int) $user['total_xp']; ?></li>
    <li>Level: <?php echo (int) $user['level']; ?></li>
  </ul>
<?php endif; ?>

<h3>Enrolled Courses</h3>
<?php if (count($enrollments) > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Course</th>
        <th>Difficulty</th>
        <th>Enrolled At</th>
        <th>Status</th>
        <th>Archived</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($enrollments as $enrollment): ?>
        <tr>
          <td>
            <a href="/kody/course.php?course_id=<?php echo (int) $enrollment['course_id']; ?>">
              <?php echo htmlspecialchars($enrollment['title']); ?>
            </a>
          </td>
          <td><?php echo htmlspecialchars($enrollment['difficulty'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($enrollment['enrolled_at']); ?></td>
          <td><?php echo htmlspecialchars($enrollment['completion_status']); ?></td>
          <td><?php echo ((int) $enrollment['is_archived'] === 1) ? 'Yes' : 'No'; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>No enrolled courses yet.</p>
  <p><a href="/kody/enroll.php">Start by enrolling in your first course</a>.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

