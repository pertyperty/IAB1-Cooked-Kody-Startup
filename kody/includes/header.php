<?php
require_once __DIR__ . '/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = '/kody';
$isLoggedIn = checkAuth();
$isUserAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kody</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <h1>Kody</h1>
    <nav>
        <a href="<?php echo $basePath; ?>/dashboard.php">Dashboard</a>
        <a href="<?php echo $basePath; ?>/course.php">Courses</a>
        <a href="<?php echo $basePath; ?>/leaderboard.php">Leaderboard</a>
        <a href="<?php echo $basePath; ?>/subscription.php">Subscription</a>
        <?php if ($isLoggedIn): ?>
            <?php if ($isUserAdmin): ?>
                <a href="<?php echo $basePath; ?>/admin/users_crud.php">Admin Panel</a>
            <?php endif; ?>
            <a href="<?php echo $basePath; ?>/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?php echo $basePath; ?>/login.php">Login</a>
            <a href="<?php echo $basePath; ?>/register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
