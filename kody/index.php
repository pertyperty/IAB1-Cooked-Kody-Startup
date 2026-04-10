<?php
require_once __DIR__ . '/includes/auth.php';

if (checkAuth()) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;
