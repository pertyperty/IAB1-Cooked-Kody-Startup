<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$allCourses = getAllCourses();
$selectedCourseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$selectedCourse = null;
$modules = [];

if ($selectedCourseId > 0) {
  $selectedCourse = getCourse($selectedCourseId);
  if ($selectedCourse) {
    $modules = getModules($selectedCourseId);
  }
}

require_once __DIR__ . '/includes/header.php';
?>
  <div class="page-hero">
  <h2>Courses</h2>
  <p>Browse the catalog, inspect modules and lessons, then jump into enrollment in one click.</p>
  <div class="page-actions mt-1">
    <a class="primary" href="/kody/enroll.php">Go to Enrollment</a>
    <a href="/kody/submit_code.php">Open Challenge Runner</a>
  </div>
</div>

<div class="split-layout">
  <section class="subtle-panel">
    <div class="section-heading">
      <h3>Available Courses</h3>
      <span class="badge info"><?php echo (int) count($allCourses); ?> total</span>
    </div>
    <?php if (count($allCourses) > 0): ?>
      <div class="card-grid mt-1">
        <?php foreach ($allCourses as $course): ?>
          <article class="content-card">
            <div class="section-heading align-start">
              <div>
                <div class="panel-eyebrow">Course <?php echo (int) $course['course_id']; ?></div>
                <h4 class="mb-025"><?php echo htmlspecialchars($course['title']); ?></h4>
              </div>
              <span class="badge <?php echo ((int) $course['is_archived'] === 1) ? 'warn' : 'success'; ?>">
                <?php echo ((int) $course['is_archived'] === 1) ? 'Archived' : 'Active'; ?>
              </span>
            </div>
            <p class="meta">Difficulty: <?php echo htmlspecialchars($course['difficulty'] ?? ''); ?></p>
            <div class="page-actions">
              <a class="button-link primary" href="/kody/course.php?course_id=<?php echo (int) $course['course_id']; ?>">View details</a>
              <a class="button-link" href="/kody/enroll.php?course_id=<?php echo (int) $course['course_id']; ?>">Enroll</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No courses found.</p>
    <?php endif; ?>
  </section>

  <aside class="subtle-panel">
    <?php if ($selectedCourseId > 0 && !$selectedCourse): ?>
      <p class="notice">Course not found.</p>
    <?php endif; ?>

    <?php if ($selectedCourse): ?>
      <div class="section-heading">
        <h3>Selected Course</h3>
        <a class="button-link primary" href="/kody/enroll.php?course_id=<?php echo (int) $selectedCourse['course_id']; ?>">Enroll</a>
      </div>
      <div class="timeline mt-075">
        <div class="timeline-item"><strong>Title:</strong> <?php echo htmlspecialchars($selectedCourse['title']); ?></div>
        <div class="timeline-item"><strong>Description:</strong> <?php echo htmlspecialchars($selectedCourse['description'] ?? ''); ?></div>
        <div class="timeline-item"><strong>Difficulty:</strong> <?php echo htmlspecialchars($selectedCourse['difficulty'] ?? ''); ?></div>
        <div class="timeline-item"><strong>Instructor:</strong> <?php echo htmlspecialchars(trim(($selectedCourse['instructor_first_name'] ?? '') . ' ' . ($selectedCourse['instructor_last_name'] ?? ''))); ?></div>
        <div class="timeline-item"><strong>Created:</strong> <?php echo htmlspecialchars($selectedCourse['created_at']); ?></div>
      </div>

      <h4 class="mt-1">Modules & Lessons</h4>
      <?php if (count($modules) > 0): ?>
        <div class="timeline">
          <?php foreach ($modules as $module): ?>
            <div class="timeline-item">
              <div class="section-heading align-start">
                <div>
                  <div class="panel-eyebrow">Module <?php echo (int) $module['module_order']; ?></div>
                  <strong><?php echo htmlspecialchars($module['module_title']); ?></strong>
                </div>
                <span class="badge dark"><?php echo count($module['lessons']); ?> lessons</span>
              </div>
              <div class="card-meta mb-035">Created At: <?php echo htmlspecialchars($module['created_at']); ?></div>
              <?php if (count($module['lessons']) > 0): ?>
                <div class="timeline mt-05">
                  <?php foreach ($module['lessons'] as $lesson): ?>
                    <div class="timeline-item timeline-item-soft">
                      <div class="section-heading align-start">
                        <strong>Lesson <?php echo (int) $lesson['lesson_order']; ?>: <?php echo htmlspecialchars($lesson['lesson_title']); ?></strong>
                        <a class="button-link" href="/kody/submit_code.php">Open runner</a>
                      </div>
                      <div class="meta meta-prewrap"><?php echo htmlspecialchars($lesson['content'] ?? ''); ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="meta">No lessons in this module yet.</p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p>No modules found for this course.</p>
      <?php endif; ?>
    <?php else: ?>
      <div class="page-hero hero-teal">
        <h3>Choose a course</h3>
        <p>Open any course card to inspect its modules, lessons, and enrollment path.</p>
      </div>
    <?php endif; ?>
  </aside>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

