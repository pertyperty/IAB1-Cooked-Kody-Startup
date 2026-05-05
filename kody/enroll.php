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
if (isset($_GET['course_id'])) {
  $prefillCourseId = (int) $_GET['course_id'];
}

require_once __DIR__ . '/includes/header.php';
?>
  <div class="page-hero">
  <h2>Enrollment</h2>
  <p>Choose a course card and enroll with one click. No dropdowns, no clutter.</p>
  <div class="page-actions mt-1">
    <a class="primary" href="/kody/course.php">Browse Courses</a>
    <a href="/kody/progress.php">View Progress</a>
  </div>
</div>

<?php if ($message !== ''): ?>
  <p class="<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<section>
  <div class="section-heading">
    <h3>Course Cards</h3>
    <span class="badge info"><?php echo (int) count($courses); ?> available</span>
  </div>
  <?php if (count($courses) > 0): ?>
    <div class="card-grid mt-1">
      <?php foreach ($courses as $course): ?>
        <article class="content-card">
          <div class="section-heading align-start">
            <div>
              <div class="panel-eyebrow">Course <?php echo (int) $course['course_id']; ?></div>
              <h4 class="mb-025"><?php echo htmlspecialchars($course['title']); ?></h4>
            </div>
            <span class="badge <?php echo ((int) $course['is_archived'] === 1) ? 'warn' : 'success'; ?>">
              <?php echo ((int) $course['is_archived'] === 1) ? 'Archived' : 'Open'; ?>
            </span>
          </div>
          <p class="meta">Difficulty: <?php echo htmlspecialchars($course['difficulty'] ?? ''); ?></p>
          <p class="meta">Description: <?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
          <form method="post" class="page-actions">
            <input type="hidden" name="course_id" value="<?php echo (int) $course['course_id']; ?>">
            <button type="submit" class="primary">Enroll now</button>
            <a class="button-link" href="/kody/course.php?course_id=<?php echo (int) $course['course_id']; ?>">View course</a>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No courses found.</p>
  <?php endif; ?>
</section>

<section>
  <div class="section-heading">
    <h3>Your Enrollment Records</h3>
    <a class="button-link" href="/kody/progress.php">Open progress tracker</a>
  </div>
  <?php if (count($enrollments) > 0): ?>
    <div class="timeline mt-1">
      <?php foreach ($enrollments as $row): ?>
        <div class="timeline-item">
          <div class="section-heading align-start">
            <div>
              <div class="panel-eyebrow">Enrollment #<?php echo (int) $row['enrollment_id']; ?></div>
              <strong><?php echo htmlspecialchars($row['title']); ?></strong>
            </div>
            <span class="badge info"><?php echo htmlspecialchars($row['completion_status']); ?></span>
          </div>
          <div class="card-meta mt-04">Difficulty: <?php echo htmlspecialchars($row['difficulty'] ?? ''); ?> | Enrolled: <?php echo htmlspecialchars($row['enrolled_at']); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No enrollment records yet.</p>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

