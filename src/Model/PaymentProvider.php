<?php

declare(strict_types=1);

namespace MockPsps\Model;

enum PaymentProvider: string {
    case FakeStripe = "fakeStripe";
    case FakePaypal = "fakePaypal";
}
