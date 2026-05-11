<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/header.php';

$pdo = connectDB();

function getCount($pdo, $table) {
    $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM ' . $table);
    $r = $stmt->fetch();
    return $r ? (int)$r['cnt'] : 0;
}

$counts = [
    'users' => getCount($pdo, 'users'),
    'courses' => getCount($pdo, 'courses'),
    'submissions' => getCount($pdo, 'submissions'),
    'payments' => getCount($pdo, 'payments'),
    'notifications' => getCount($pdo, 'notifications'),
    'requests' => getCount($pdo, 'instructor_requests'),
    'reviews' => getCount($pdo, 'moderation_reviews'),
];

// recent submissions
$recentStmt = $pdo->query("SELECT s.submission_id, s.challenge_id, s.user_id, s.execution_status, s.score, s.submitted_at, u.first_name, u.last_name, ch.title AS challenge_title
    FROM submissions s
    LEFT JOIN users u ON u.user_id = s.user_id
    LEFT JOIN challenges ch ON ch.challenge_id = s.challenge_id
    ORDER BY s.submitted_at DESC LIMIT 8");
$recent = $recentStmt->fetchAll();
?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <h3>Admin</h3>
        <a href="users_crud.php">Users</a>
        <a href="roles_crud.php">Roles</a>
        <a href="user_roles_crud.php">User Roles</a>
        <a href="courses_crud.php">Courses</a>
        <a href="modules_crud.php">Modules</a>
        <a href="lessons_crud.php">Lessons</a>
        <a href="challenges_crud.php">Challenges</a>
        <a href="submissions_crud.php">Submissions</a>
        <a href="leaderboard_crud.php">Leaderboard</a>
        <a href="enrollment_crud.php">Enrollments</a>
        <a href="progress_crud.php">Progress</a>
        <a href="subscriptions_crud.php">Subscription Plans</a>
        <a href="user_subscriptions_crud.php">User Subscriptions</a>
        <a href="payments_crud.php">Payments</a>
        <a href="instructor_requests_crud.php">Instructor Requests</a>
        <a href="testcases_crud.php">Test Cases</a>
        <a href="user_xp_crud.php">User XP</a>
        <a href="moderation_reviews_crud.php">Moderation Reviews</a>
        <a href="notifications_crud.php">Notifications</a>
    </aside>

    <div class="admin-main">
        <div class="page-hero admin-hero">
            <h2>Admin Dashboard</h2>
            <p>Monitor system health, review key data, and manage schema-backed workflows from one place.</p>
        </div>

        <div class="card-grid mt-1">
            <div class="info-card">
                <div class="eyebrow">Users</div>
                <div class="metric"><?php echo $counts['users']; ?></div>
                <div class="card-meta">Registered accounts</div>
            </div>
            <div class="info-card">
                <div class="eyebrow">Courses</div>
                <div class="metric"><?php echo $counts['courses']; ?></div>
                <div class="card-meta">Course records</div>
            </div>
            <div class="info-card">
                <div class="eyebrow">Submissions</div>
                <div class="metric"><?php echo $counts['submissions']; ?></div>
                <div class="card-meta">Code submissions</div>
            </div>
            <div class="info-card">
                <div class="eyebrow">Payments</div>
                <div class="metric"><?php echo $counts['payments']; ?></div>
                <div class="card-meta">Payment rows</div>
            </div>
            <div class="info-card">
                <div class="eyebrow">Notifications</div>
                <div class="metric"><?php echo $counts['notifications']; ?></div>
                <div class="card-meta">Inbox entries</div>
            </div>
            <div class="info-card">
                <div class="eyebrow">Requests</div>
                <div class="metric"><?php echo $counts['requests']; ?></div>
                <div class="card-meta">Instructor requests</div>
            </div>
        </div>

        <section class="subtle-panel mt-1">
            <div class="section-heading">
                <h3>Recent Submissions</h3>
                <span class="badge info"><?php echo (int) count($recent); ?> latest</span>
            </div>
            <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr><th>ID</th><th>User</th><th>Challenge</th><th>Status</th><th>Score</th><th>Submitted At</th></tr>
                </thead>
                <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="6">No recent submissions.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><?php echo (int)$r['submission_id']; ?></td>
                            <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['challenge_title']); ?></td>
                            <td><?php echo htmlspecialchars($r['execution_status']); ?></td>
                            <td><?php echo htmlspecialchars($r['score']); ?></td>
                            <td><?php echo htmlspecialchars($r['submitted_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </section>

        <section class="subtle-panel mt-1">
            <div class="section-heading">
                <h3>Quick Actions</h3>
                <span class="badge success">Shortcuts</span>
            </div>
            <div class="page-actions mt-075">
                <a class="button-link primary" href="users_crud.php">Manage Users</a>
                <a class="button-link" href="notifications_crud.php">Manage Notifications</a>
                <a class="button-link" href="instructor_requests_crud.php">Review Requests</a>
                <a class="button-link" href="moderation_reviews_crud.php">Moderation Reviews</a>
                <a class="button-link" href="../notifications.php">Open User Inbox</a>
            </div>
        </section>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
