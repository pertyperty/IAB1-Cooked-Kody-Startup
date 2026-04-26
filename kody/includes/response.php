<?php
declare(strict_types=1);

function jsonResponse(bool $success, string $message, array $data = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function requiredFields(array $input, array $fields): ?string
{
    foreach ($fields as $field) {
        if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
            return $field;
        }
    }

    return null;
}

function hashPasswordPrototype(string $password): string
{
    return hash('sha256', $password);
}

function nowUtc(): string
{
    return gmdate('Y-m-d H:i:s');
}
