<?php

declare(strict_types=1);

namespace MockPsps\Tests\Repository;

use MockPsps\Model\Merchant;
use MockPsps\Model\ApiKeyValidator;
use MockPsps\Repository\MySqlMerchantRepository;
use PHPUnit\Framework\TestCase;

final class MySqlMerchantRepositoryTest extends TestCase
{
    private \PDO $pdo;
    private MySqlMerchantRepository $repository;

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

        $this->pdo->exec('TRUNCATE TABLE merchants');

        $this->repository = new MySqlMerchantRepository($this->pdo);
    }

    public function testMerchantFindById(): void
    {
        $plainApiKey = 'amco-live-key-1234567890123456789012';
        $merchant = new Merchant(
            id: 'amco',
            name: 'Amco',
            pspName: 'fakeStripe',
            apiKeyHash: ApiKeyValidator::hash($plainApiKey),
            email: 'amco@amco.example',
        );

        $this->repository->create($merchant);

        $found = $this->repository->findById('amco');

        self::assertNotNull($found);
        self::assertSame('Amco', $found->name);
        self::assertSame('fakeStripe', $found->pspName);
    }

    public function testMerchantFindByApiKey(): void
    {
        $plainApiKey = 'amco-live-key-1234567890123456789012';
        $merchant = new Merchant(
            id: 'amco',
            name: 'Amco',
            pspName: 'fakePaypal',
            apiKeyHash: ApiKeyValidator::hash($plainApiKey),
            email: 'amco@amco.example',
        );

        $this->repository->create($merchant);

        $found = $this->repository->findByApiKey($plainApiKey);

        self::assertNotNull($found);
        self::assertSame('amco', $found->id);
    }

    public function testMerchantRemove(): void
    {
        $plainApiKey = 'amco-live-key-1234567890123456789012';
        $merchant = new Merchant(
            id: 'amco',
            name: 'Amco',
            pspName: 'fakeStripe',
            apiKeyHash: ApiKeyValidator::hash($plainApiKey),
            email: 'amco@amco.example',
        );

        $this->repository->create($merchant);

        $removed = $this->repository->remove('amco');

        self::assertTrue($removed);
        self::assertNull($this->repository->findById('amco'));
    }

    public function testMerchantFindByIdNull(): void
    {
        $found = $this->repository->findById('missing-amco');

        self::assertNull($found);
    }
}
