<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'tchs_info_board';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        http_response_code(503);
        $safeMessage = 'The information board database is unavailable. Please check the installation and database configuration.';
        if (PHP_SAPI === 'cli') {
            throw $exception;
        }
        exit('<!doctype html><html><body style="font-family:sans-serif;padding:3rem"><h1>Service unavailable</h1><p>' . $safeMessage . '</p></body></html>');
    }

    return $pdo;
}

