<?php

declare(strict_types=1);

namespace MockPsps\Tests\Repository;

use MockPsps\Model\Charge;
use MockPsps\Repository\MySqlChargeRepository;
use PHPUnit\Framework\TestCase;

final class MySqlChargeRepositoryTest extends TestCase
{
    private \PDO $pdo;
    private MySqlChargeRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new \PDO(
            'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
            getenv('DB_USER'),
            getenv('DB_PASS'),
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

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

        $this->repository = new MySqlChargeRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('TRUNCATE TABLE charges');
    }

    public function testSaveThenFindById(): void
    {
        $charge = new Charge(
            id: 'amco-001',
            merchantId: 'amco-retail',
            amount: 1000,
            currency: 'EUR',
            status: 'success',
            transactionId: 'tx-retail-1',
            createdAt: '2026-03-10T10:00:00+00:00',
        );

        $this->repository->save($charge);

        $found = $this->repository->findById('amco-001');

        self::assertNotNull($found);
        self::assertSame(1000, $found->amount);
        self::assertSame('success', $found->status);
    }

    public function testDateRangeQuery(): void
    {
        $this->repository->save(new Charge('amco-food-1', 'amco-food', 1000, 'EUR', 'success', 'tx-food-1', '2026-03-10T10:00:00+00:00'));
        $this->repository->save(new Charge('amco-food-2', 'amco-food', 500, 'EUR', 'failed', 'tx-food-2', '2026-03-12T10:00:00+00:00'));
        $this->repository->save(new Charge('amco-travel-1', 'amco-travel', 700, 'EUR', 'success', 'tx-travel-1', '2026-03-11T10:00:00+00:00'));

        $charges = $this->repository->findByMerchantIdAndDateRange(
            'amco-food',
            '2026-03-01T00:00:00+00:00',
            '2026-03-31T23:59:59+00:00',
        );

        self::assertCount(2, $charges);
        self::assertSame('amco-food', $charges[0]->merchantId);
        self::assertSame('amco-food', $charges[1]->merchantId);
    }

    public function testDateRangeQueryNoMatch(): void
    {
        $this->repository->save(new Charge('amco-solo-1', 'amco-solo', 900, 'EUR', 'success', 'tx-solo-1', '2026-03-10T10:00:00+00:00'));

        $charges = $this->repository->findByMerchantIdAndDateRange(
            'amco-solo',
            '2026-04-01T00:00:00+00:00',
            '2026-04-30T23:59:59+00:00',
        );

        self::assertSame([], $charges);
    }
}
