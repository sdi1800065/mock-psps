<?php

declare(strict_types=1);

namespace MockPsps\Tests\Service;

use MockPsps\Email\EmailSenderInterface;
use MockPsps\Model\Charge;
use MockPsps\Model\Merchant;
use MockPsps\Repository\ChargeRepositoryInterface;
use MockPsps\Service\ChargeReportService;
use PHPUnit\Framework\TestCase;

final class ChargeReportServiceTest extends TestCase
{
    public function testItThrowsWhenFromDateIsAfterToDate(): void
    {
        $repository = new ChargeRepositoryFake([]);
        $sender = new RecordingEmailSenderFake('smtp://test:1025');
        $service = new ChargeReportService($repository, $sender);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('from date must be before or equal to to date');

        $service->sendMerchantReport(
            $this->createMerchant(),
            new \DateTimeImmutable('2026-03-24 00:00:00'),
            new \DateTimeImmutable('2026-03-23 00:00:00'),
        );
    }

    public function testItSendsNoChargesMessageWhenRepositoryIsEmpty(): void
    {
        $repository = new ChargeRepositoryFake([]);
        $sender = new RecordingEmailSenderFake('smtp://test:1025');
        $service = new ChargeReportService($repository, $sender);

        $result = $service->sendMerchantReport(
            $this->createMerchant(),
            new \DateTimeImmutable('2026-03-01 00:00:00'),
            new \DateTimeImmutable('2026-03-23 23:59:59'),
        );

        self::assertSame('smtp://test:1025', $result);
        self::assertNotNull($sender->lastBody);
        self::assertStringContainsString('No charges found for the selected period.', $sender->lastBody);
    }

    public function testItCalculatesTotalsAndCountsCorrectly(): void
    {
        $charges = [
            new Charge('charge-1', 'merchant-1', 1000, 'EUR', 'success', 'tx-1', '2026-03-10T10:00:00+00:00'),
            new Charge('charge-2', 'merchant-1', 500, 'EUR', 'success', 'tx-2', '2026-03-11T10:00:00+00:00'),
            new Charge('charge-3', 'merchant-1', 300, 'EUR', 'failed', 'tx-3', '2026-03-12T10:00:00+00:00'),
        ];

        $repository = new ChargeRepositoryFake($charges);
        $sender = new RecordingEmailSenderFake('smtp://test:1025');
        $service = new ChargeReportService($repository, $sender);

        $service->sendMerchantReport(
            $this->createMerchant(),
            new \DateTimeImmutable('2026-03-01 00:00:00'),
            new \DateTimeImmutable('2026-03-23 23:59:59'),
        );

        self::assertNotNull($sender->lastBody);
        self::assertStringContainsString('Total charges: 3', $sender->lastBody);
        self::assertStringContainsString('Successful charges: 2', $sender->lastBody);
        self::assertStringContainsString('Failed charges: 1', $sender->lastBody);
        self::assertStringContainsString('Total amount: 1800 cents', $sender->lastBody);
    }

    public function testItSendsToMerchantEmailAndReturnsSenderResult(): void
    {
        $repository = new ChargeRepositoryFake([]);
        $sender = new RecordingEmailSenderFake('smtp://mailpit:1025');
        $service = new ChargeReportService($repository, $sender);

        $merchant = $this->createMerchant();
        $result = $service->sendMerchantReport(
            $merchant,
            new \DateTimeImmutable('2026-03-01 00:00:00'),
            new \DateTimeImmutable('2026-03-23 23:59:59'),
        );

        self::assertSame('smtp://mailpit:1025', $result);
        self::assertSame($merchant->email, $sender->lastTo);
        self::assertNotNull($sender->lastSubject);
        self::assertStringContainsString('Charge report for', $sender->lastSubject);
    }

    private function createMerchant(): Merchant
    {
        return new Merchant(
            id: 'merchant-1',
            name: 'Acme Corp',
            pspName: 'fakeStripe',
            apiKeyHash: 'test-key',
            email: 'acme@example.com',
        );
    }
}

final class ChargeRepositoryFake implements ChargeRepositoryInterface
{
    private array $charges;

    public ?array $lastRangeArgs = null;

    public function __construct(array $charges)
    {
        $this->charges = $charges;
    }

    public function save(Charge $charge): void
    {
    }

    public function findByMerchantIdAndDateRange(string $merchantId, string $from, string $to): array
    {
        $this->lastRangeArgs = [
            'merchantId' => $merchantId,
            'from' => $from,
            'to' => $to,
        ];

        return $this->charges;
    }

    public function findById(string $id): ?Charge
    {
        return null;
    }
}

final class RecordingEmailSenderFake implements EmailSenderInterface
{
    public ?string $lastTo = null;
    public ?string $lastSubject = null;
    public ?string $lastBody = null;

    public function __construct(private string $result)
    {
    }

    public function send(string $to, string $subject, string $body): string
    {
        $this->lastTo = $to;
        $this->lastSubject = $subject;
        $this->lastBody = $body;

        return $this->result;
    }
}