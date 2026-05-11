<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = '/kody';
$isLoggedIn = checkAuth();
$isUserAdmin = isAdmin();
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$unreadNotificationCount = $currentUserId > 0 ? getUnreadNotificationCount($currentUserId) : 0;

// detect current script for breadcrumb/title hints
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kody</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/style.css">
    <?php if ($isUserAdmin): ?>
        <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/admin.css">
    <?php endif; ?>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header">
    <div class="container header-shell">
        <div class="header-meta">
            <div class="header-brand">
                <a href="<?php echo $basePath; ?>/index.php" class="site-title">Kody</a>
                <?php if ($isUserAdmin && strpos($currentPath, '/admin/') !== false): ?>
                    <div class="muted small">Admin Panel — <?php echo htmlspecialchars($currentScript); ?></div>
                <?php endif; ?>
            </div>
            <?php if ($isLoggedIn): ?>
                <div class="header-actions" aria-label="Quick shortcuts">
                    <a class="btn btn-primary" href="<?php echo $basePath; ?>/dashboard.php">Dashboard</a>
                    <a class="btn btn-ghost" href="<?php echo $basePath; ?>/course.php">Courses</a>
                    <a class="btn btn-ghost" href="<?php echo $basePath; ?>/enroll.php">Enroll</a>
                    <a class="btn btn-ghost" href="<?php echo $basePath; ?>/submit_code.php">Submit Code</a>
                    <a class="btn btn-ghost" href="<?php echo $basePath; ?>/progress.php">Progress</a>
                    <a class="btn btn-ghost" href="<?php echo $basePath; ?>/subscription.php">Subscription</a>
                    <a class="btn btn-ghost" href="<?php echo $basePath; ?>/notifications.php">Notifications<?php if ($unreadNotificationCount > 0): ?> (<?php echo (int) $unreadNotificationCount; ?>)<?php endif; ?></a>
                    <a class="btn btn-ghost" href="<?php echo $basePath; ?>/instructor_request.php">Instructor Request</a>
                    <a class="btn btn-ghost" href="<?php echo $basePath; ?>/leaderboard.php">Leaderboard</a>
                </div>
            <?php endif; ?>
        </div>

        <nav class="site-nav" aria-label="Main navigation">
            <?php if ($isLoggedIn): ?>
                <?php if ($isUserAdmin): ?>
                    <a class="btn btn-ghost" href="<?php echo $basePath; ?>/admin/users_crud.php">Admin Panel</a>
                <?php endif; ?>
                <span class="muted ml-075">Hello, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?></span>
                <a class="btn btn-ghost" href="<?php echo $basePath; ?>/logout.php">Logout</a>
            <?php else: ?>
                <a class="btn btn-ghost" href="<?php echo $basePath; ?>/login.php">Login</a>
                <a class="btn btn-ghost" href="<?php echo $basePath; ?>/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main id="main-content" class="container">
<?php
// Render flash message as a floating toast for JS to enhance
$flash = getCrudFlashMessage();
if ($flash) {
    $cls = $flash['type'] === 'success' ? 'crud-flash success' : 'crud-flash error';
    echo '<div class="' . $cls . '">' . htmlspecialchars($flash['message']) . '</div>';
}
?>
