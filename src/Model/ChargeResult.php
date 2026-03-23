<?php

declare(strict_types=1);

namespace MockPsps\Model;

class ChargeResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $transactionId,
        public readonly string $message,
    ){}
}