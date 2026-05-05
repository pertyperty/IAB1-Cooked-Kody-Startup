<?php
require_once __DIR__ . '/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = '/kody';
$isLoggedIn = checkAuth();
$isUserAdmin = isAdmin();

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
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;">
            <a href="<?php echo $basePath; ?>/index.php" style="text-decoration:none;color:inherit;"><h1>Kody</h1></a>
            <?php if ($isUserAdmin && strpos($currentPath, '/admin/') !== false): ?>
                <div style="font-size:0.95rem;color:var(--muted);">Admin Panel — <?php echo htmlspecialchars($currentScript); ?></div>
            <?php endif; ?>
        </div>

        <nav aria-label="Main navigation">
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo $basePath; ?>/dashboard.php">Dashboard</a>
                <a href="<?php echo $basePath; ?>/course.php">Courses</a>
                <a href="<?php echo $basePath; ?>/enroll.php">Enroll</a>
                <a href="<?php echo $basePath; ?>/progress.php">Progress</a>
                <a href="<?php echo $basePath; ?>/submit_code.php">Submit Code</a>
                <a href="<?php echo $basePath; ?>/leaderboard.php">Leaderboard</a>
                <a href="<?php echo $basePath; ?>/subscription.php">Subscription</a>
                <?php if ($isUserAdmin): ?>
                    <a href="<?php echo $basePath; ?>/admin/users_crud.php">Admin Panel</a>
                <?php endif; ?>
                <span style="margin-left:0.75rem;color:var(--muted);">Hello, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?></span>
                <a href="<?php echo $basePath; ?>/logout.php">Logout</a>
            <?php else: ?>
                <a href="<?php echo $basePath; ?>/login.php">Login</a>
                <a href="<?php echo $basePath; ?>/register.php">Register</a>
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
