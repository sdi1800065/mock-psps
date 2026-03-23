<?php

declare(strict_types=1);

namespace MockPsps\Psp;

use MockPsps\Model\ChargeResult;
use MockPsps\Psp\PspInterface;

class FakePaypal implements PspInterface {
    public function charge(array $params) : ChargeResult
    {   
        foreach(['amount','email','password'] as $field)
        {
            if (!isset($params[$field]))
            {
                throw new \InvalidArgumentException("Missing field: $field");
            }
        }

        return new ChargeResult(
            success: true,
            transactionId: "paypal_" . uniqid(),
            message: "The charge was successful",
        );
    }
}