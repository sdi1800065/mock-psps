<?php 

declare(strict_types=1);

namespace MockPsps\Repository;

use MockPsps\Repository\MerchantRepositoryInterface;
use MockPsps\Model\Merchant;
use MockPsps\Model\ApiKeyValidator;


class MySqlMerchantRepository implements MerchantRepositoryInterface
{
    public function __construct(private \PDO $pdo){}
    public function create(Merchant $merchant): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO merchants VALUES (:id, :name, :psp_name, :api_key_hash, :email)'
        );
        $stmt->execute([
            ':id'            => $merchant->id,
            ':name'          => $merchant->name,
            ':psp_name'      => $merchant->pspName,
            ':api_key_hash'  => $merchant->apiKeyHash,
            ':email'         => $merchant->email,
        ]);
    }

    public function remove(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM merchants WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
    public function findById(string $id):?Merchant
    {
        $stmt = $this->pdo->prepare('SELECT * FROM merchants WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return new Merchant(
            id: $row['id'],
            name: $row['name'],
            pspName: $row['psp_name'],
            apiKeyHash: $row['api_key_hash'],
            email: $row['email']
        );
    }

    public function findByApiKey(string $apiKey):?Merchant
    {
        $stmt = $this->pdo->prepare('SELECT * FROM merchants');
        $stmt->execute();
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (ApiKeyValidator::verify($apiKey, $row['api_key_hash'])) {
                return new Merchant(
                    id: $row['id'],
                    name: $row['name'],
                    pspName: $row['psp_name'],
                    apiKeyHash: $row['api_key_hash'],
                    email: $row['email']
                );
            }
        }
        return null;
    }
}