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

$payload = extractCrudData($_POST, $table);

if (!$payload['success']) {
    setCrudFlashMessage('error', $payload['message']);
    header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
    exit;
}

$result = createRecord($table, $payload['data']);
setCrudFlashMessage($result['success'] ? 'success' : 'error', $result['message']);
header('Location: ' . getCrudRedirectTarget($table, $_POST['module'] ?? null));
exit;
