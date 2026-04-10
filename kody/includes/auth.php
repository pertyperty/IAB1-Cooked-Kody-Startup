<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAuth()
{
    return isset($_SESSION['user_id']);
}

function requireAuth()
{
    if (!checkAuth()) {
        header('Location: login.php');
        exit;
    }
}

function getCurrentUser()
{
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? 'Guest',
        'last_name' => $_SESSION['last_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
    ];
}
