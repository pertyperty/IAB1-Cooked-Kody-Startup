<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function checkAuth()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return !empty($_SESSION['is_admin']);
}

function requireAuth()
{
    if (!checkAuth()) {
        header('Location: /kody/login.php');
        exit;
    }
}

function requireAdmin()
{
    requireAuth();

    if (!isAdmin()) {
        header('Location: /kody/dashboard.php');
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
