<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$currentUser = getCurrentUser();
$userId = (int) $currentUser['user_id'];

$message = '';
$messageType = 'notice';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int) ($_POST['course_id'] ?? 0);

    if ($courseId <= 0) {
        $message = 'Please select a valid course.';
    } else {
        $result = enrollUser($userId, $courseId);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'notice';
    }
}

$courses = getAllCourses();
$enrollments = getUserEnrollments($userId);

require_once __DIR__ . '/includes/header.php';
?>
<h2>Course Enrollment</h2>

<?php if ($message !== ''): ?>
  <p class="<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<h3>Enroll in a Course</h3>
<form method="post">
  <label for="course_id">Course</label><br>
  <select id="course_id" name="course_id" required>
    <option value="">Select a course</option>
    <?php foreach ($courses as $course): ?>
      <option value="<?php echo (int) $course['course_id']; ?>">
        <?php echo htmlspecialchars($course['title']); ?>
        (<?php echo htmlspecialchars($course['difficulty'] ?? ''); ?>)
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <button type="submit">Enroll</button>
</form>

<h3>Your Enrollment Records</h3>
<?php if (count($enrollments) > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Enrollment ID</th>
        <th>Course ID</th>
        <th>Course Title</th>
        <th>Difficulty</th>
        <th>Enrolled At</th>
        <th>Completion Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($enrollments as $row): ?>
        <tr>
          <td><?php echo (int) $row['enrollment_id']; ?></td>
          <td><?php echo (int) $row['course_id']; ?></td>
          <td><?php echo htmlspecialchars($row['title']); ?></td>
          <td><?php echo htmlspecialchars($row['difficulty'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($row['enrolled_at']); ?></td>
          <td><?php echo htmlspecialchars($row['completion_status']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>No enrollment records yet.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

