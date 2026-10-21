<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'racik_pos';
const DB_USER = 'root';
const DB_PASS = '';

function db(bool $withoutDatabase = false): PDO
{
    static $connection = null;
    if (!$withoutDatabase && $connection instanceof PDO) {
        return $connection;
    }

    $database = $withoutDatabase ? '' : ';dbname=' . DB_NAME;
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . $database . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        if (!$withoutDatabase && str_contains($exception->getMessage(), 'Unknown database')) {
            header('Location: setup.php');
            exit;
        }
        throw $exception;
    }

    if (!$withoutDatabase) {
        $connection = $pdo;
    }
    return $pdo;
}
