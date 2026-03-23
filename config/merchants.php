<?php

declare(strict_types=1);

use MockPsps\Model\Merchant;
use MockPsps\Model\PspName;

return [
    new Merchant(
        id: 'merchant-1',
        name: 'Acme Corp',
        pspName: PspName::FakeStripe,
        pspConfig: [],
        apiKey: 'test-key-stripe-123',
        email: 'acme@example.com',
    ),
    new Merchant(
        id: 'merchant-2',
        name: 'Globex LLC',
        pspName: PspName::FakePaypal,
        pspConfig: [],
        apiKey: 'test-key-paypal-456',
        email: 'globex@example.com',
    ),
];