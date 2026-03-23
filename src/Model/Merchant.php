<?php

declare(strict_types=1);

namespace MockPsps\Model;

use MockPsps\Model\PspName;

class Merchant
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly PspName $pspName,
        public readonly array  $pspConfig,
        public readonly string $apiKey,
        public readonly string $email,
    ){}
}