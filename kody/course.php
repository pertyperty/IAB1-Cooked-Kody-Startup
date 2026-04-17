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
<h2>Course Page</h2>

<h3>Available Courses</h3>
<?php if (count($allCourses) > 0): ?>
  <ul>
    <?php foreach ($allCourses as $course): ?>
      <li>
        <a href="/kody/course.php?course_id=<?php echo (int) $course['course_id']; ?>">
          <?php echo htmlspecialchars($course['title']); ?>
        </a>
        (<?php echo htmlspecialchars($course['difficulty'] ?? ''); ?>)
      </li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <p>No courses found.</p>
<?php endif; ?>

<?php if ($selectedCourseId > 0 && !$selectedCourse): ?>
  <p class="notice">Course not found.</p>
<?php endif; ?>

<?php if ($selectedCourse): ?>
  <h3>Selected Course Details</h3>
  <ul>
    <li>Course ID: <?php echo (int) $selectedCourse['course_id']; ?></li>
    <li>Title: <?php echo htmlspecialchars($selectedCourse['title']); ?></li>
    <li>Description: <?php echo htmlspecialchars($selectedCourse['description'] ?? ''); ?></li>
    <li>Difficulty: <?php echo htmlspecialchars($selectedCourse['difficulty'] ?? ''); ?></li>
    <li>Instructor: <?php echo htmlspecialchars(trim(($selectedCourse['instructor_first_name'] ?? '') . ' ' . ($selectedCourse['instructor_last_name'] ?? ''))); ?></li>
    <li>Created At: <?php echo htmlspecialchars($selectedCourse['created_at']); ?></li>
    <li>Archived: <?php echo ((int) $selectedCourse['is_archived'] === 1) ? 'Yes' : 'No'; ?></li>
  </ul>

  <h3>Modules and Lessons</h3>
  <?php if (count($modules) > 0): ?>
    <?php foreach ($modules as $module): ?>
      <section>
        <h4>Module <?php echo (int) $module['module_order']; ?>: <?php echo htmlspecialchars($module['module_title']); ?></h4>
        <p>Created At: <?php echo htmlspecialchars($module['created_at']); ?></p>

        <?php if (count($module['lessons']) > 0): ?>
          <ul>
            <?php foreach ($module['lessons'] as $lesson): ?>
              <li>
                Lesson <?php echo (int) $lesson['lesson_order']; ?>:
                <strong><?php echo htmlspecialchars($lesson['lesson_title']); ?></strong><br>
                <?php echo htmlspecialchars($lesson['content'] ?? ''); ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p>No lessons in this module yet.</p>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No modules found for this course.</p>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

