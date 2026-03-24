<?php

declare(strict_types=1);

namespace MockPsps\Tests\Service;

use MockPsps\Model\Merchant;
use MockPsps\Repository\MerchantRepositoryInterface;
use MockPsps\Service\MerchantService;
use PHPUnit\Framework\TestCase;

final class MerchantServiceTest extends TestCase
{
    public function testCreatesMerchant(): void
    {
        $repository = new InMemoryMerchantRepositoryFake();
        $service = new MerchantService($repository);

        $merchant = $service->create([
            'name' => 'Amco',
            'pspName' => 'fakeStripe',
            'apiKey' => 'amco-live-key',
            'email' => 'amco@amco.example',
        ]);

        self::assertSame('Amco', $merchant->name);
        self::assertSame('fakeStripe', $merchant->pspName);
        self::assertSame('amco-live-key', $merchant->apiKey);
        self::assertNotEmpty($merchant->id);
        self::assertNotNull($repository->createdMerchant);
        self::assertSame($merchant->id, $repository->createdMerchant->id);
    }

    public function testRejectsUnsupportedPaymentProvider(): void
    {
        $service = new MerchantService(new InMemoryMerchantRepositoryFake());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pspName must be either FakeStripe or FakePaypal');

        $service->create([
            'name' => 'EveryPay',
            'pspName' => 'unknownPsp',
            'apiKey' => 'everypay-key',
            'email' => 'ops@everypay.example',
        ]);
    }

    public function testRemove(): void
    {
        $repository = new InMemoryMerchantRepositoryFake();
        $service = new MerchantService($repository);

        $removed = $service->remove(['id' => 'merchant-uk-001']);

        self::assertTrue($removed);
        self::assertSame('merchant-uk-001', $repository->lastRemovedId);
    }

    public function testRemoveRequiresIdField(): void
    {
        $service = new MerchantService(new InMemoryMerchantRepositoryFake());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('id is required');

        $service->remove([]);
    }

    public function testCreateRequiresEmailField(): void
    {
        $service = new MerchantService(new InMemoryMerchantRepositoryFake());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('email is required');

        $service->create([
            'name' => 'No Mail',
            'pspName' => 'fakePaypal',
            'apiKey' => 'no-mail-key',
        ]);
    }
}

final class InMemoryMerchantRepositoryFake implements MerchantRepositoryInterface
{
    public ?Merchant $createdMerchant = null;
    public ?string $lastRemovedId = null;

    public function create(Merchant $merchant): void
    {
        $this->createdMerchant = $merchant;
    }

    public function remove(string $id): bool
    {
        $this->lastRemovedId = $id;
        return true;
    }

    public function findById(string $id): ?Merchant
    {
        return null;
    }

    public function findByApiKey(string $apiKey): ?Merchant
    {
        return null;
    }
}
