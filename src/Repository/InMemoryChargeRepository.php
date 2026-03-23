<?php 

declare(strict_types=1);

namespace MockPsps\Repository;

use MockPsps\Repository\ChargeRepositoryInterface;
use MockPsps\Model\Charge;


class InMemoryChargeRepository implements ChargeRepositoryInterface
{
    private array $charges=[];

    public function save(Charge $charge):void
    {
        $this->charges[$charge->id] = $charge;
    }
    public function findById(string $id):?Charge
    {
        return $this->charges[$id] ?? null;
    }
    public function findByMerchantIdAndDateRange(string $merchantId, string $from, string $to):array
    {
        $merchantCharges = [];
        foreach($this->charges as $charge)
        {
            if($charge->merchantId === $merchantId)
            {
                $merchantCharges[] = $charge;
            }
        }
        return $merchantCharges;    
    }
}