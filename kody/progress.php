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
<div class="page-hero">
  <h2>User Progress</h2>
  <p>Track learning by course, module, lesson, and challenge through status cards and quick updates.</p>
</div>

<?php if ($message !== ''): ?>
  <p class="<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<section class="split-layout">
  <div class="subtle-panel">
    <div class="section-heading">
      <h3>Add Progress Row</h3>
      <span class="badge info">Manual tracking</span>
    </div>
    <form method="post" class="mt-1">
      <input type="hidden" name="action" value="add_progress">

      <label for="course_id">Course</label>
      <select id="course_id" name="course_id" required>
        <option value="">Select course</option>
        <?php foreach ($options['courses'] as $course): ?>
          <option value="<?php echo (int) $course['course_id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
        <?php endforeach; ?>
      </select>

      <div class="card-grid split-2 mt-075">
        <div>
          <label for="module_id">Module (optional)</label>
          <select id="module_id" name="module_id">
            <option value="">None</option>
            <?php foreach ($options['modules'] as $module): ?>
              <option value="<?php echo (int) $module['module_id']; ?>"><?php echo htmlspecialchars($module['title']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="lesson_id">Lesson (optional)</label>
          <select id="lesson_id" name="lesson_id">
            <option value="">None</option>
            <?php foreach ($options['lessons'] as $lesson): ?>
              <option value="<?php echo (int) $lesson['lesson_id']; ?>"><?php echo htmlspecialchars($lesson['title']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <label for="challenge_id" class="mt-075">Challenge (optional)</label>
      <select id="challenge_id" name="challenge_id">
        <option value="">None</option>
        <?php foreach ($options['challenges'] as $challenge): ?>
          <option value="<?php echo (int) $challenge['challenge_id']; ?>"><?php echo htmlspecialchars($challenge['title']); ?></option>
        <?php endforeach; ?>
      </select>

      <label for="status" class="mt-075">Status</label>
      <select id="status" name="status" required>
        <option value="not_started">not_started</option>
        <option value="in_progress">in_progress</option>
        <option value="completed">completed</option>
      </select>

      <div class="page-actions mt-1">
        <button type="submit" class="primary">Add Progress</button>
      </div>
    </form>
  </div>

  <div class="subtle-panel">
    <div class="section-heading">
      <h3>Progress overview</h3>
      <span class="badge success"><?php echo (int) count($progressRows); ?> rows</span>
    </div>
    <?php if (count($progressRows) > 0): ?>
      <div class="timeline mt-1">
        <?php foreach ($progressRows as $row): ?>
          <article class="timeline-item">
            <div class="section-heading align-start">
              <div>
                <div class="panel-eyebrow">Progress #<?php echo (int) $row['progress_id']; ?></div>
                <strong><?php echo htmlspecialchars($row['course_title'] ?? 'Course'); ?></strong>
              </div>
              <span class="badge <?php echo $row['status'] === 'completed' ? 'success' : 'info'; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
            </div>
            <div class="card-meta mt-04">
              Module: <?php echo htmlspecialchars($row['module_title'] ?? ''); ?> · Lesson: <?php echo htmlspecialchars($row['lesson_title'] ?? ''); ?> · Challenge: <?php echo htmlspecialchars($row['challenge_title'] ?? ''); ?>
            </div>
            <?php if (!empty($row['completed_at'])): ?>
              <div class="card-meta">Completed at: <?php echo htmlspecialchars($row['completed_at']); ?></div>
            <?php endif; ?>
            <form method="post" class="page-actions mt-075">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="progress_id" value="<?php echo (int) $row['progress_id']; ?>">
              <select name="status" required>
                <option value="not_started" <?php echo $row['status'] === 'not_started' ? 'selected' : ''; ?>>not_started</option>
                <option value="in_progress" <?php echo $row['status'] === 'in_progress' ? 'selected' : ''; ?>>in_progress</option>
                <option value="completed" <?php echo $row['status'] === 'completed' ? 'selected' : ''; ?>>completed</option>
              </select>
              <button type="submit" class="primary">Save status</button>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No progress rows yet.</p>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

