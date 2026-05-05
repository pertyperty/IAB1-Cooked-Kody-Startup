<?php
// Simple PHP syntax linter for the project. Run: php scripts/check_php_syntax.php
$root = __DIR__ . '/..';
$paths = [
    $root . '/kody',
    $root . '/includes',
    $root . '/admin'
];

$files = [];
foreach ($paths as $p) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p));
    foreach ($it as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

$errors = [];
foreach ($files as $f) {
    $cmd = 'php -l ' . escapeshellarg($f) . ' 2>&1';
    $out = shell_exec($cmd);
    if (strpos($out, 'No syntax errors detected') === false) {
        $errors[$f] = trim($out);
    }
}

if (empty($errors)) {
    echo "All PHP files passed syntax check.\n";
    exit(0);
}

echo "Syntax errors found:\n";
foreach ($errors as $file => $msg) {
    echo "- $file\n  $msg\n";
}

exit(1);
