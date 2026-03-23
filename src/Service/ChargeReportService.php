<?php

declare(strict_types=1);

namespace MockPsps\Service;

use MockPsps\Email\EmailSenderInterface;
use MockPsps\Model\Merchant;
use MockPsps\Repository\ChargeRepositoryInterface;

class ChargeReportService
{
    public function __construct(
        private ChargeRepositoryInterface $chargeRepository,
        private EmailSenderInterface $emailSender,
    ) {
    }

    public function sendMerchantReport(Merchant $merchant, \DateTimeImmutable $from, \DateTimeImmutable $to): string
    {
        if ($from > $to) 
        {
            throw new \InvalidArgumentException('from date must be before or equal to to date');
        }

        $charges = $this->chargeRepository->findByMerchantIdAndDateRange(
            $merchant->id,
            $from->format(\DateTimeInterface::ATOM),
            $to->format(\DateTimeInterface::ATOM),
        );

        $totalAmount = 0;
        $successCount = 0;
        $failedCount = 0;

        foreach ($charges as $charge) 
        {
            $totalAmount += $charge->amount;

            if ($charge->status === 'success') {
                $successCount++;
                continue;
            }

            if ($charge->status === 'failed') {
                $failedCount++;
            }
        }

        $subject = sprintf(
            'Charge report for %s (%s to %s)',
            $merchant->name,
            $from->format('Y-m-d'),
            $to->format('Y-m-d')
        );

        $body = implode("\n", [
            sprintf('Merchant: %s', $merchant->name),
            sprintf('Merchant ID: %s', $merchant->id),
            sprintf('Period: %s to %s', $from->format(\DateTimeInterface::ATOM), $to->format(\DateTimeInterface::ATOM)),
            sprintf('Total charges: %d', count($charges)),
            sprintf('Successful charges: %d', $successCount),
            sprintf('Failed charges: %d', $failedCount),
            sprintf('Total amount: %d cents', $totalAmount),
            '',
            'Charge details:',
            ...$this->formatChargeLines($charges),
        ]);

        return $this->emailSender->send($merchant->email, $subject, $body);
    }

    private function formatChargeLines(array $charges): array
    {
        if ($charges === []) 
        {
            return ['No charges found for the selected period.'];
        }

        $lines = [];

        foreach ($charges as $charge) 
        {
            $lines[] = sprintf(
                '- %s | %s | %d %s | %s',
                $charge->createdAt,
                $charge->id,
                $charge->amount,
                $charge->currency,
                $charge->status,
            );
        }

        return $lines;
    }
}