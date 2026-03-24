<?php 

declare(strict_types=1);

namespace MockPsps\Repository;

use MockPsps\Repository\MerchantRepositoryInterface;
use MockPsps\Model\Merchant;


class MySqlMerchantRepository implements MerchantRepositoryInterface
{
    public function __construct(private \PDO $pdo){}
    public function create(Merchant $merchant): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO merchants VALUES (:id, :name, :psp_name, :api_key, :email)'
        );
        $stmt->execute([
            ':id'            => $merchant->id,
            ':name'          => $merchant->name,
            ':psp_name'      => $merchant->pspName,
            ':api_key'       => $merchant->apiKey,
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
            apiKey: $row['api_key'],
            email: $row['email']
        );
    }
    public function findByApiKey(string $apiKey):?Merchant
    {
        $stmt = $this->pdo->prepare('SELECT * FROM merchants WHERE api_key = :api_key');
        $stmt->execute([':api_key' => $apiKey]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return new Merchant(
            id: $row['id'],
            name: $row['name'],
            pspName: $row['psp_name'],
            apiKey: $row['api_key'],
            email: $row['email']
        );
    }
}