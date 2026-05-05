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
        <a href="payments_crud.php">Payments</a>
        <a href="notifications_crud.php">Notifications</a>
    </aside>

    <div class="admin-main">
        <h2>Admin Dashboard</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?php echo $counts['users']; ?></div>
                <div class="label">Users</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo $counts['courses']; ?></div>
                <div class="label">Courses</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo $counts['submissions']; ?></div>
                <div class="label">Submissions</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo $counts['payments']; ?></div>
                <div class="label">Payments</div>
            </div>
        </div>

        <section>
            <h3>Recent Submissions</h3>
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

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
