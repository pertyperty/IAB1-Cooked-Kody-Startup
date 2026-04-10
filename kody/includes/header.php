<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kody</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <h1>Kody</h1>
    <nav>
        <a href="/index.php">Home</a>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/leaderboard.php">Leaderboard</a>
    </nav>
</header>
<main class="container">
