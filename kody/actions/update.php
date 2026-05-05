<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request method.');
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setCrudFlashMessage('error', 'Security check failed. Please try again.');
    header('Location: ' . getCrudRedirectTarget(null, $_POST['module'] ?? null));
    exit;
}

$table = resolveCrudTableName($_POST['table'] ?? null, $_POST['module'] ?? null);

if (!$table) {
    setCrudFlashMessage('error', 'Unable to resolve a whitelisted table for this request.');
    header('Location: ' . getCrudRedirectTarget(null, $_POST['module'] ?? null));
    exit;
}

$recordId = $_POST['id'] ?? $_POST['record_id'] ?? null;

if (empty($recordId)) {
    setCrudFlashMessage('error', 'Missing record ID for update.');
    header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
    exit;
}

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Preserve password hashes when editing users unless explicitly changed.
if ($table === 'users' && array_key_exists('password_hash', $_POST) && trim((string) $_POST['password_hash']) === '') {
    unset($_POST['password_hash']);
}

// Handle file uploads (profile pictures etc.) with server-side checks
$maxSize = 2 * 1024 * 1024; // 2MB
$mimeToExt = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
];
foreach ($_FILES as $field => $fileinfo) {
    if (empty($fileinfo['name'])) continue;
    if ($fileinfo['error'] !== UPLOAD_ERR_OK) continue;

    if ($fileinfo['size'] > $maxSize) {
        setCrudFlashMessage('error', 'Uploaded file too large for ' . $field . '. Max 2MB.');
        header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo ? finfo_file($finfo, $fileinfo['tmp_name']) : null;
    if ($finfo) {
        finfo_close($finfo);
    }

    if (empty($detectedMime) || !isset($mimeToExt[$detectedMime])) {
        setCrudFlashMessage('error', 'Invalid file type for ' . $field . '.');
        header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
        exit;
    }

    $safeName = bin2hex(random_bytes(16)) . '.' . $mimeToExt[$detectedMime];
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

$result = updateRecord($table, $recordId, $payload['data']);
setCrudFlashMessage($result['success'] ? 'success' : 'error', $result['message']);
header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
exit;
