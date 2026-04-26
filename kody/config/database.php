<?php
declare(strict_types=1);

function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('KODY_DB_HOST') ?: '127.0.0.1';
    $port = getenv('KODY_DB_PORT') ?: '3306';
    $name = getenv('KODY_DB_NAME') ?: 'kody_db';
    $user = getenv('KODY_DB_USER') ?: 'root';
    $pass = getenv('KODY_DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
