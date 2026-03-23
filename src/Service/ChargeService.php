<?php

declare(strict_types=1);

namespace MockPsps\Service;
use MockPsps\Repository\ChargeRepositoryInterface;
use MockPsps\Psp\PspInterface;
use MockPsps\Model\Merchant;
use MockPsps\Model\Charge;

class ChargeService {
    
    public function __construct(
        private ChargeRepositoryInterface $chargeRepository ,
        private array $psps,
    ) {}

    public function charge(Merchant $merchant, array $params): Charge
    {
        if (!isset($params['amount']) || !is_int($params['amount']) || $params['amount'] <= 0) {
            throw new \InvalidArgumentException('amount must be a positive integer (cents)');
        }
        if (empty($params['currency']) || !is_string($params['currency'])) {
            throw new \InvalidArgumentException('currency is required');
        }

        $merchantPsp = $this->psps[$merchant->pspName->value] ?? null;
        if(empty($merchantPsp)) throw new \RuntimeException("PSP not configured");
        $chargeResult = $merchantPsp->charge($params);
        $charge = new Charge(
            id : uniqid('charge_'),
            merchantId : $merchant->id,
            amount : $params['amount'],
            currency : $params['currency'],
            status : $chargeResult->success ? 'success' : 'failed',
            transactionId : $chargeResult->transactionId,
            createdAt : (new \DateTimeImmutable())->format(\DateTime::ATOM),
        );
        $this->chargeRepository->save($charge);
        return $charge;
    }
}