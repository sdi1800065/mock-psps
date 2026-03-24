<?php

declare(strict_types=1);

namespace MockPsps\Tests\Api;

use PHPUnit\Framework\TestCase;

final class ApiEndpointsTest extends TestCase
{
    private static $serverProcess = null;
    private \PDO $pdo;

    public static function setUpBeforeClass(): void
    {

        self::$serverProcess = proc_open(
            'php -S 127.0.0.1:8099 -t public',
            [],
            $pipes,
            dirname(__DIR__, 2)
        );

        if (!is_resource(self::$serverProcess)) {
            self::fail('Failed to start built-in PHP server for API tests');
        }

        // wait server to boot.
        usleep(10000);
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$serverProcess)) {
            proc_terminate(self::$serverProcess);
            proc_close(self::$serverProcess);
            self::$serverProcess = null;
        }
    }

    protected function setUp(): void
    {
        $this->pdo = new \PDO(
            'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
            getenv('DB_USER'),
            getenv('DB_PASS'),
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS merchants (
            id VARCHAR(64) PRIMARY KEY,
            name VARCHAR(64) NOT NULL,
            psp_name VARCHAR(64) NOT NULL,
            api_key_hash VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(128) NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS charges (
            id VARCHAR(64) PRIMARY KEY,
            merchant_id VARCHAR(64) NOT NULL,
            amount INT NOT NULL,
            currency VARCHAR(8) NOT NULL,
            status VARCHAR(16) NOT NULL,
            transaction_id VARCHAR(128) NOT NULL,
            created_at VARCHAR(32) NOT NULL
        )');

        $this->pdo->exec('TRUNCATE TABLE charges');
        $this->pdo->exec('TRUNCATE TABLE merchants');
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('TRUNCATE TABLE charges');
        $this->pdo->exec('TRUNCATE TABLE merchants');
    }

    public function testAdminCanRegisterMerchant(): void
    {
        $response = $this->request(
            method: 'POST',
            path: '/merchant/add',
            authHeader: 'Bearer ' . (getenv('ADMIN_TOKEN') ?: ''),
            jsonBody: [
                'name' => 'amco',
                'pspName' => 'fakeStripe',
                'email' => 'hello@amco.example',
            ],
        );

        self::assertSame(201, $response['statusCode']);
        self::assertArrayHasKey('id', $response['body']);
        self::assertSame('amco', $response['body']['name']);
        self::assertSame('fakeStripe', $response['body']['pspName']);
        self::assertArrayHasKey('apiKey', $response['body']);
    }

    public function testMerchantCanChargeAfterRegistration(): void
    {
        $createMerchantResponse = $this->request(
            method: 'POST',
            path: '/merchant/add',
            authHeader: 'Bearer ' . (getenv('ADMIN_TOKEN') ?: ''),
            jsonBody: [
                'name' => 'amco',
                'pspName' => 'fakeStripe',
                'email' => 'accounts@amco.example',
            ],
        );

        self::assertSame(201, $createMerchantResponse['statusCode']);
        $merchantApiKey = $createMerchantResponse['body']['apiKey'];
        self::assertNotEmpty($merchantApiKey);

        $chargeResponse = $this->request(
            method: 'POST',
            path: '/merch',
            authHeader: 'Bearer ' . $merchantApiKey,
            jsonBody: [
                'amount' => 1500,
                'currency' => 'EUR',
                'cardNumber' => '4242424242424242',
                'cvv' => '123',
                'expiryMonth' => '12',
                'expiryYear' => '2030',
            ],
        );

        self::assertSame(201, $chargeResponse['statusCode']);
        self::assertSame('success', $chargeResponse['body']['status']);
        self::assertArrayHasKey('transactionId', $chargeResponse['body']);
    }

    public function testAdminRouteRejectsInvalidToken(): void
    {
        $response = $this->request(
            method: 'POST',
            path: '/merchant/add',
            authHeader: 'Bearer definitely-not-the-admin-token',
            jsonBody: [
                'name' => 'fail',
                'pspName' => 'fakeStripe',
                'email' => 'fail@example.com',
            ],
        );

        self::assertSame(401, $response['statusCode']);
        self::assertSame('Unauthorized', $response['body']['error']);
    }

    private function request(string $method, string $path, string $authHeader, array $jsonBody): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => [
                    'Authorization: ' . $authHeader,
                    'Content-Type: application/json',
                ],
                // We want response bodies even on 401/422 to assert error payloads.
                'content' => json_encode($jsonBody, JSON_THROW_ON_ERROR),
                'ignore_errors' => true,
                'timeout' => 5,
            ],
        ]);

        $stdout = @file_get_contents('http://127.0.0.1:8099' . $path, false, $context);
        if ($stdout === false) {
            self::fail('HTTP request failed for path: ' . $path);
        }

        $headers = $http_response_header ?? [];
        $statusLine = $headers[0] ?? '';
        if (!preg_match('#HTTP/\S+\s+(\d+)#', $statusLine, $matches)) {
            self::fail('Could not determine HTTP status code. Status line: ' . $statusLine);
        }

        $decoded = json_decode($stdout, true);
        if (!is_array($decoded)) {
            self::fail('Expected JSON response body. STDOUT: ' . $stdout);
        }

        return [
            'statusCode' => (int) $matches[1],
            'body' => $decoded,
        ];
    }
}
