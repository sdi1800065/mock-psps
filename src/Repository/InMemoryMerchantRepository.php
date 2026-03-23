<?php 

declare(strict_types=1);

namespace MockPsps\Repository;

use MockPsps\Repository\MerchantRepositoryInterface;
use MockPsps\Model\Merchant;


class InMemoryMerchantRepository implements MerchantRepositoryInterface
{
    private array $merchants =[];
    public function __construct(array $merchants)
    {
        foreach ($merchants as $m) 
        {
            $this->merchants[$m->id] = $m; 
        }
    }
    public function findById(string $id):?Merchant
    {
        return $this->merchants[$id] ?? null;
    }
    public function findByApiKey(string $apiKey):?Merchant
    {
        foreach($this->merchants as $merchant)
        {
            if($merchant->apiKey === $apiKey)
            {
                return $merchant;
            }
        }
        return null;    
    }
}