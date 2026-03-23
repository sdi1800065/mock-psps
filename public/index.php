<?php

declare(strict_types=1); // Throw errors on type mismatches

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
    'message' => 'MockPsps Payment Service is running',
]);
