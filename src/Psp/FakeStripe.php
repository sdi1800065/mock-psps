<?php

declare(strict_types=1);

namespace MockPsps\Psp;

use MockPsps\Model\ChargeResult;
use MockPsps\Psp\PspInterface;

class FakeStripe implements PspInterface {
    public function charge(array $params) : ChargeResult
    {   
        foreach(['amount','cardNumber','cvv','expiryMonth','expiryYear'] as $field)
        {
            if (!isset($params[$field]))
            {
                throw new \InvalidArgumentException("Missing field: $field");
            }
        }

        return new ChargeResult(
            success: true,
            transactionId: "stripe_" . uniqid(),
            message: "The charge was successful",
        );
    }
}