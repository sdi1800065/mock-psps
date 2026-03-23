<?php

declare(strict_types=1);

namespace MockPsps\Tests\Service;

use MockPsps\Model\Charge;
use MockPsps\Model\Merchant;
use MockPsps\Model\PspName;
use MockPsps\Psp\FakeStripe;
use MockPsps\Repository\ChargeRepositoryInterface;
use MockPsps\Service\ChargeService;
use PHPUnit\Framework\TestCase;

final class ChargeServiceTest extends TestCase
{
    public function testItCreatesSuccessfulChargeForValidInput(): void
    {
        $repository = new InMemoryChargeRepositoryFake();
        $service = new ChargeService($repository, [
            'fakeStripe' => new FakeStripe(),
        ]);

        $merchant = $this->createMerchant(PspName::FakeStripe);

        $result = $service->charge($merchant, [
            'amount' => 1000,
            'currency' => 'EUR',
            'cardNumber' => '4242424242424242',
            'cvv' => '123',
            'expiryMonth' => '12',
            'expiryYear' => '2030',
        ]);

        self::assertSame('success', $result->status);
        self::assertSame($merchant->id, $result->merchantId);
        self::assertNotEmpty($result->id);
        self::assertNotNull($repository->savedCharge);
        self::assertSame($result->id, $repository->savedCharge->id);
    }

    public function testItThrowsForInvalidAmount(): void
    {
        $service = new ChargeService(new InMemoryChargeRepositoryFake(), [
            'fakeStripe' => new FakeStripe(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('amount must be a positive integer (cents)');

        $service->charge($this->createMerchant(PspName::FakeStripe), [
            'amount' => 0,
            'currency' => 'EUR',
            'cardNumber' => '4242424242424242',
            'cvv' => '123',
            'expiryMonth' => '12',
            'expiryYear' => '2030',
        ]);
    }

    public function testItThrowsWhenCurrencyMissing(): void
    {
        $service = new ChargeService(new InMemoryChargeRepositoryFake(), [
            'fakeStripe' => new FakeStripe(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('currency is required');

        $service->charge($this->createMerchant(PspName::FakeStripe), [
            'amount' => 1000,
            'cardNumber' => '4242424242424242',
            'cvv' => '123',
            'expiryMonth' => '12',
            'expiryYear' => '2030',
        ]);
    }

    public function testItThrowsWhenMerchantPspIsNotConfigured(): void
    {
        $service = new ChargeService(new InMemoryChargeRepositoryFake(), [
            'fakeStripe' => new FakeStripe(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PSP not configured');

        $service->charge($this->createMerchant(PspName::FakePaypal), [
            'amount' => 1000,
            'currency' => 'EUR',
            'email' => 'buyer@example.com',
            'password' => 'secret',
        ]);
    }

    private function createMerchant(PspName $pspName): Merchant
    {
        return new Merchant(
            id: 'merchant-1',
            name: 'Acme Corp',
            pspName: $pspName,
            pspConfig: [],
            apiKey: 'test-key',
            email: 'acme@example.com',
        );
    }
}

final class InMemoryChargeRepositoryFake implements ChargeRepositoryInterface
{
    public ?Charge $savedCharge = null;

    public function save(Charge $charge): void
    {
        $this->savedCharge = $charge;
    }

    public function findByMerchantIdAndDateRange(string $merchantId, string $from, string $to): array
    {
        return [];
    }

    public function findById(string $id): ?Charge
    {
        return null;
    }
}