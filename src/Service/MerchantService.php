<?php

declare(strict_types=1);

namespace MockPsps\Service;
use MockPsps\Repository\MerchantRepositoryInterface;
use MockPsps\Model\PaymentProvider;
use MockPsps\Model\Merchant;
use Ramsey\Uuid\Uuid;

class MerchantService
{
    public function __construct(
        private MerchantRepositoryInterface $merchantRepository,
    ) {}

    public function create(array $params): Merchant
    {
        if (!isset($params['name']) || !is_string($params['name'])) {
            throw new \InvalidArgumentException('name is required');
        }
        if (!isset($params['pspName']) || !is_string($params['pspName'])) {
            throw new \InvalidArgumentException('pspName is required');
        }
        if (!isset($params['apiKey']) || !is_string($params['apiKey'])) {
            throw new \InvalidArgumentException('apiKey is required');
        }
        if (!isset($params['email']) || !is_string($params['email'])) {
            throw new \InvalidArgumentException('email is required');
        }
        if (PaymentProvider::tryFrom($params['pspName']) === null) {
            throw new \InvalidArgumentException('pspName must be either FakeStripe or FakePaypal');
        }   

        $merchant = new Merchant(
            id : 'merchant_' . Uuid::uuid7()->toString(),
            name : $params['name'],
            pspName : $params['pspName'],
            apiKey : $params['apiKey'],
            email : $params['email'],
        );
        $this->merchantRepository->create($merchant);
        return $merchant;        
    }
    public function remove(array $params): bool
    {
        if (empty($params['id']) || !is_string($params['id'])) {
            throw new \InvalidArgumentException('id is required');
        }
        return $this->merchantRepository->remove($params['id']);
    }
}