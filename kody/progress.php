<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$currentUser = getCurrentUser();
$userId = (int) $currentUser['user_id'];

$message = '';
$messageType = 'notice';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add_progress') {
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $moduleId = (int) ($_POST['module_id'] ?? 0);
    $lessonId = (int) ($_POST['lesson_id'] ?? 0);
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);
    $status = $_POST['status'] ?? 'not_started';

    $allowedStatuses = ['not_started', 'in_progress', 'completed'];
    if ($courseId <= 0) {
      $message = 'Course is required when adding progress.';
    } elseif (!in_array($status, $allowedStatuses, true)) {
      $message = 'Invalid status selected.';
    } else {
      $ok = createProgressRow($userId, $courseId, $moduleId, $lessonId, $challengeId, $status);
      $message = $ok ? 'Progress row added.' : 'Unable to add progress row.';
      $messageType = $ok ? 'success' : 'notice';
    }
  }

  if ($action === 'update_status') {
    $progressId = (int) ($_POST['progress_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowedStatuses = ['not_started', 'in_progress', 'completed'];

    if ($progressId <= 0 || !in_array($status, $allowedStatuses, true)) {
      $message = 'Invalid progress update request.';
    } else {
      $ok = updateProgressStatus($userId, $progressId, $status);
      $message = $ok ? 'Progress status updated.' : 'Unable to update progress.';
      $messageType = $ok ? 'success' : 'notice';
    }
  }
}

$progressRows = getProgressRows($userId);
$options = getProgressOptionLists();

require_once __DIR__ . '/includes/header.php';
?>
<h2>User Progress</h2>

<?php if ($message !== ''): ?>
  <p class="<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<h3>Add Progress Row</h3>
<form method="post">
  <input type="hidden" name="action" value="add_progress">

  <label for="course_id">Course</label><br>
  <select id="course_id" name="course_id" required>
    <option value="">Select course</option>
    <?php foreach ($options['courses'] as $course): ?>
      <option value="<?php echo (int) $course['course_id']; ?>">
        <?php echo htmlspecialchars($course['title']); ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label for="module_id">Module (optional)</label><br>
  <select id="module_id" name="module_id">
    <option value="">None</option>
    <?php foreach ($options['modules'] as $module): ?>
      <option value="<?php echo (int) $module['module_id']; ?>">
        <?php echo htmlspecialchars($module['title']); ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label for="lesson_id">Lesson (optional)</label><br>
  <select id="lesson_id" name="lesson_id">
    <option value="">None</option>
    <?php foreach ($options['lessons'] as $lesson): ?>
      <option value="<?php echo (int) $lesson['lesson_id']; ?>">
        <?php echo htmlspecialchars($lesson['title']); ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label for="challenge_id">Challenge (optional)</label><br>
  <select id="challenge_id" name="challenge_id">
    <option value="">None</option>
    <?php foreach ($options['challenges'] as $challenge): ?>
      <option value="<?php echo (int) $challenge['challenge_id']; ?>">
        <?php echo htmlspecialchars($challenge['title']); ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label for="status">Status</label><br>
  <select id="status" name="status" required>
    <option value="not_started">not_started</option>
    <option value="in_progress">in_progress</option>
    <option value="completed">completed</option>
  </select><br><br>

  <button type="submit">Add Progress</button>
</form>

<h3>Your Progress Rows</h3>
<?php if (count($progressRows) > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Progress ID</th>
        <th>Course</th>
        <th>Module</th>
        <th>Lesson</th>
        <th>Challenge</th>
        <th>Status</th>
        <th>Completed At</th>
        <th>Update Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($progressRows as $row): ?>
        <tr>
          <td><?php echo (int) $row['progress_id']; ?></td>
          <td>
            <?php echo (int) ($row['course_id'] ?? 0); ?>
            - <?php echo htmlspecialchars($row['course_title'] ?? ''); ?>
          </td>
          <td>
            <?php echo (int) ($row['module_id'] ?? 0); ?>
            - <?php echo htmlspecialchars($row['module_title'] ?? ''); ?>
          </td>
          <td>
            <?php echo (int) ($row['lesson_id'] ?? 0); ?>
            - <?php echo htmlspecialchars($row['lesson_title'] ?? ''); ?>
          </td>
          <td>
            <?php echo (int) ($row['challenge_id'] ?? 0); ?>
            - <?php echo htmlspecialchars($row['challenge_title'] ?? ''); ?>
          </td>
          <td><?php echo htmlspecialchars($row['status']); ?></td>
          <td><?php echo htmlspecialchars($row['completed_at'] ?? ''); ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="progress_id" value="<?php echo (int) $row['progress_id']; ?>">
              <select name="status" required>
                <option value="not_started" <?php echo $row['status'] === 'not_started' ? 'selected' : ''; ?>>not_started</option>
                <option value="in_progress" <?php echo $row['status'] === 'in_progress' ? 'selected' : ''; ?>>in_progress</option>
                <option value="completed" <?php echo $row['status'] === 'completed' ? 'selected' : ''; ?>>completed</option>
              </select>
              <button type="submit">Save</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>No progress rows yet.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

