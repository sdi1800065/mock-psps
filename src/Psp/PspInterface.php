<?php 

declare(strict_types=1);

namespace MockPsps\Psp;

use MockPsps\Model\ChargeResult;

interface PspInterface
{
    public function charge(array $params): ChargeResult;
}