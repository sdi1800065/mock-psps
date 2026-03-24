<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec('
    CREATE TABLE IF NOT EXISTS merchants (
        id VARCHAR(64) PRIMARY KEY,
        name VARCHAR(64) NOT NULL,
        psp_name VARCHAR(64) NOT NULL,
        api_key_hash VARCHAR(255) NOT NULL UNIQUE,
        email VARCHAR(128) NOT NULL
    )
');

$pdo->exec('
    CREATE TABLE IF NOT EXISTS charges (
        id VARCHAR(64) PRIMARY KEY,
        merchant_id VARCHAR(64) NOT NULL,
        amount INT NOT NULL,
        currency VARCHAR(8) NOT NULL,
        status VARCHAR(16) NOT NULL,
        transaction_id VARCHAR(128) NOT NULL,
        created_at VARCHAR(32) NOT NULL
    )
');

fwrite(STDOUT, "Database tables created successfully.\n");