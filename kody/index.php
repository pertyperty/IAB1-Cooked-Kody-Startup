<?php
require_once __DIR__ . '/includes/auth.php';

if (checkAuth()) {
    $landingPath = isAdmin() ? '/kody/admin/users_crud.php' : '/kody/dashboard.php';
    header('Location: ' . $landingPath);
    exit;
}

header('Location: /kody/login.php');
exit;
