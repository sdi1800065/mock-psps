<?php

declare(strict_types=1);

namespace MockPsps\Repository;

use MockPsps\Model\Merchant;

interface MerchantRepositoryInterface{
    public function findById(string $id):?Merchant;
    public function findByApiKey(string $apiKey):?Merchant;
}