<?php

declare(strict_types=1);

namespace MockPsps\Model;

class Charge {
    public function __construct(
        public readonly string $id,
        public readonly string $merchantId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly string $transactionId,
        public readonly string $createdAt,
    ){}
}