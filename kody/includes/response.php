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
    return password_hash($password, PASSWORD_DEFAULT);
}

function isLegacySha256Hash(string $storedHash): bool
{
    return preg_match('/^[a-f0-9]{64}$/i', $storedHash) === 1;
}

function verifyPasswordPrototype(string $plainPassword, string $storedHash): bool
{
    if ($storedHash === '') {
        return false;
    }

    $hashInfo = password_get_info($storedHash);
    if (($hashInfo['algo'] ?? null) !== null && (int) $hashInfo['algo'] !== 0) {
        return password_verify($plainPassword, $storedHash);
    }

    if (isLegacySha256Hash($storedHash)) {
        return hash_equals(strtolower($storedHash), hash('sha256', $plainPassword));
    }

    return false;
}

function passwordNeedsUpgrade(string $storedHash): bool
{
    if ($storedHash === '' || isLegacySha256Hash($storedHash)) {
        return true;
    }

    $hashInfo = password_get_info($storedHash);
    if (($hashInfo['algo'] ?? null) === null || (int) $hashInfo['algo'] === 0) {
        return true;
    }

    return password_needs_rehash($storedHash, PASSWORD_DEFAULT);
}

function nowUtc(): string
{
    return gmdate('Y-m-d H:i:s');
}
