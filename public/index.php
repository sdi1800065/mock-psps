<?php

declare(strict_types=1); // Throw errors on type mismatches

require_once __DIR__ . '/../vendor/autoload.php';

use MockPsps\Repository\InMemoryMerchantRepository;
use MockPsps\Repository\MySqlChargeRepository;
use MockPsps\Psp\FakeStripe;
use MockPsps\Psp\FakePaypal;
use MockPsps\Service\ChargeService;

header('Content-Type: application/json');

$merchants = require __DIR__ . '/../config/merchants.php';
$merchantRepository = new InMemoryMerchantRepository($merchants);
$pdo = new \PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASS'),
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
);
$chargeRepository = new MySqlChargeRepository($pdo);
$chargeService = new ChargeService($chargeRepository, [
    'fakeStripe' => new FakeStripe(),
    'fakePaypal' => new FakePaypal(),
]);

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Authenticate
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$apiKey = substr($authHeader, 7);
$merchant = $merchantRepository->findByApiKey($apiKey);

if ($merchant === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Route
if ($method === 'POST' && $path === '/charge') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    try {
        $charge = $chargeService->charge($merchant, $body);
        http_response_code(201);
        echo json_encode(['id' => $charge->id, 'status' => $charge->status, 'transactionId' => $charge->transactionId]);
    } catch (\InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()]);
    } catch (\RuntimeException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);