<?php

declare(strict_types=1);

namespace MockPsps\Repository;

use MockPsps\Model\Merchant;

interface MerchantRepositoryInterface{
    public function create(Merchant $merchant):void;
    public function remove(string $id):bool;
    public function findById(string $id):?Merchant;
    public function findByApiKey(string $apiKey):?Merchant;
}