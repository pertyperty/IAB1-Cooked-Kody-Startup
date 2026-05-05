<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request method.');
}

$table = resolveCrudTableName($_POST['table'] ?? null, $_POST['module'] ?? null);

if (!$table) {
    setCrudFlashMessage('error', 'Unable to resolve a whitelisted table for this request.');
    header('Location: ' . getCrudRedirectTarget(null, $_POST['module'] ?? null));
    exit;
}

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Handle file uploads (profile pictures etc.) with server-side checks
$maxSize = 2 * 1024 * 1024; // 2MB
foreach ($_FILES as $field => $fileinfo) {
    if (empty($fileinfo['name'])) continue;
    if ($fileinfo['error'] !== UPLOAD_ERR_OK) continue;

    if ($fileinfo['size'] > $maxSize) {
        setCrudFlashMessage('error', 'Uploaded file too large for ' . $field . '. Max 2MB.');
        header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
        exit;
    }

    $allowed = ['image/jpeg','image/png','image/gif'];
    if (!in_array($fileinfo['type'], $allowed, true)) {
        setCrudFlashMessage('error', 'Invalid file type for ' . $field . '.');
        header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
        exit;
    }

    $ext = pathinfo($fileinfo['name'], PATHINFO_EXTENSION);
    $safeName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $uploadDir . $safeName;

    if (@move_uploaded_file($fileinfo['tmp_name'], $dest)) {
        // expose filename in post payload for processing
        $_POST[$field] = $safeName;
    }
}

$payload = extractCrudData($_POST, $table);

// Server-side validation
$validation = validateCrudData($table, $payload['data']);
if (!$validation['success']) {
    setCrudFlashMessage('error', $validation['message']);
    header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
    exit;
}

if (!$payload['success']) {
    setCrudFlashMessage('error', $payload['message']);
    header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
    exit;
}

$result = createRecord($table, $payload['data']);
setCrudFlashMessage($result['success'] ? 'success' : 'error', $result['message']);
header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
exit;
