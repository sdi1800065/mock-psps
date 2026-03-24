<?php

declare(strict_types=1); 

require_once __DIR__ . '/../vendor/autoload.php';

use MockPsps\Repository\MySqlMerchantRepository;
use MockPsps\Repository\MySqlChargeRepository;
use MockPsps\Psp\FakeStripe;
use MockPsps\Psp\FakePaypal;
use MockPsps\Service\ChargeService;
use MockPsps\Service\MerchantService;

header('Content-Type: application/json');

$pdo = new \PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASS'),
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
);

$merchantRepository = new MySqlMerchantRepository($pdo);
$merchantService = new MerchantService($merchantRepository);

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
$isAdminRoute = in_array($path, ['/merchant/add', '/merchant/remove'], true);

if ($isAdminRoute) {
    $adminToken = getenv('ADMIN_TOKEN');
    $providedToken = substr($authHeader, 7);
    if (!$adminToken || !hash_equals($adminToken, $providedToken)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
} 
else 
{
    $apiKey = substr($authHeader, 7);
    $merchant = $merchantRepository->findByApiKey($apiKey);

    if ($merchant === null) 
    {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
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
        echo json_encode(['error' => 'Failed to process charge']);
    } catch (\RuntimeException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
}

if ($method === 'POST' && $path === '/merchant/add') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    try {
        $result = $merchantService->create($body);
        $merchant = $result['merchant'];
        $apiKey = $result['apiKey'];
        http_response_code(201);
        echo json_encode([
            'id' => $merchant->id, 
            'name' => $merchant->name, 
            'pspName' => $merchant->pspName, 
            'apiKey' => $apiKey,
            'email' => $merchant->email
        ]);
    } catch (\InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['error' => 'Failed to create merchant']);
    } catch (\RuntimeException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
}

if ($method === 'POST' && $path === '/merchant/remove') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    try {
        $ec = $merchantService->remove($body);    
        http_response_code(201);
        echo json_encode(['success' => $ec]);
    } catch (\InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['error' => 'Failed to remove merchant']);
    } catch (\RuntimeException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
}
http_response_code(404);
echo json_encode(['error' => 'Not found']);