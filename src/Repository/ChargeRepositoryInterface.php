<?php 

declare(strict_types=1);

namespace MockPsps\Repository;
use MockPsps\Model\Charge;

interface ChargeRepositoryInterface{
    public function save(Charge $charge):void;
    public function findByMerchantIdAndDateRange(string $merchantId, string $from, string $to):array;
    public function findById(string $id):?Charge;
}