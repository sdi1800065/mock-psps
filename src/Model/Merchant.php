<?php

declare(strict_types=1);

namespace MockPsps\Model;

class Merchant
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $pspName,
        public readonly string $apiKey,
        public readonly string $email,
    ){}
}