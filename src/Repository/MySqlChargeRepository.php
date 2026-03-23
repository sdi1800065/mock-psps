<?php

declare(strict_types=1);

namespace MockPsps\Repository;

use MockPsps\Model\Charge;

class MySqlChargeRepository implements ChargeRepositoryInterface
{
    public function __construct(private \PDO $pdo)
    {
        $this->pdo->exec('
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
    }

    public function save(Charge $charge): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO charges VALUES (:id, :merchant_id, :amount, :currency, :status, :transaction_id, :created_at)'
        );
        $stmt->execute([
            ':id'             => $charge->id,
            ':merchant_id'    => $charge->merchantId,
            ':amount'         => $charge->amount,
            ':currency'       => $charge->currency,
            ':status'         => $charge->status,
            ':transaction_id' => $charge->transactionId,
            ':created_at'     => $charge->createdAt,
        ]);
    }

    public function findById(string $id): ?Charge
    {
        $stmt = $this->pdo->prepare('SELECT * FROM charges WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function findByMerchantIdAndDateRange(string $merchantId, string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM charges WHERE merchant_id = :merchant_id AND created_at BETWEEN :from AND :to'
        );
        $stmt->execute([':merchant_id' => $merchantId, ':from' => $from, ':to' => $to]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function hydrate(array $row): Charge
    {
        return new Charge(
            id:            $row['id'],
            merchantId:    $row['merchant_id'],
            amount:        (int) $row['amount'],
            currency:      $row['currency'],
            status:        $row['status'],
            transactionId: $row['transaction_id'],
            createdAt:     $row['created_at'],
        );
    }
}