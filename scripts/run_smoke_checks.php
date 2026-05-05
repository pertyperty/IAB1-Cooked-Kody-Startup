<?php
// Basic smoke checks for the project environment. Run: php scripts/run_smoke_checks.php
require_once __DIR__ . '/../kody/includes/functions.php';

$ok = true;
echo "Running smoke checks...\n";

// 1) Check upload dir writable
$uploadDir = __DIR__ . '/../kody/assets/uploads';
if (!is_dir($uploadDir)) {
    echo "- uploads dir missing, attempting to create...\n";
    if (!@mkdir($uploadDir, 0755, true)) {
        echo "  FAILED to create uploads dir: $uploadDir\n";
        $ok = false;
    } else {
        echo "  created uploads dir.\n";
    }
}
if (!is_writable($uploadDir)) {
    echo "- uploads dir not writable: $uploadDir\n";
    $ok = false;
} else {
    echo "- uploads dir writable.\n";
}

// 2) Check essential includes
$required = [
    __DIR__ . '/../kody/includes/db.php',
    __DIR__ . '/../kody/includes/auth.php',
    __DIR__ . '/../kody/includes/functions.php'
];
foreach ($required as $r) {
    if (!file_exists($r)) {
        echo "- Missing required file: $r\n";
        $ok = false;
    } else {
        echo "- Found: $r\n";
    }
}

// 3) Basic DB connect attempt if env configured
try {
    $pdo = connectDB();
    if ($pdo) {
        echo "- Database connection available.\n";
    }
} catch (Exception $e) {
    echo "- Database connection failed: " . $e->getMessage() . "\n";
    $ok = false;
}

echo $ok ? "SMOKE CHECKS PASSED\n" : "SMOKE CHECKS FAILED\n";
exit($ok ? 0 : 2);
