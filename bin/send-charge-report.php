<?php

declare(strict_types=1);

use MockPsps\Email\FileEmailSender;
use MockPsps\Email\SmtpEmailSender;
use MockPsps\Repository\MySqlMerchantRepository;
use MockPsps\Repository\MySqlChargeRepository;
use MockPsps\Service\ChargeReportService;

require_once __DIR__ . '/../vendor/autoload.php';

$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$options = getopt('', ['merchant:', 'from:', 'to:']);

if (!isset($options['merchant'], $options['from'], $options['to'])) 
{
    fwrite(STDERR, "Usage: php bin/send-charge-report.php --merchant=merchant-1 --from=2026-03-01 --to=2026-03-23\n");
    exit(1);
}

try 
{
    $from = new DateTimeImmutable($options['from'] . ' 00:00:00');
    $to = new DateTimeImmutable($options['to'] . ' 23:59:59');
} 
catch (Exception $exception) 
{
    fwrite(STDERR, "Invalid date format. Use YYYY-MM-DD for --from and --to.\n");
    exit(1);
}

$merchantRepository = new MySqlMerchantRepository($pdo);
$merchant = $merchantRepository->findById($options['merchant']);

if ($merchant === null) 
{
    fwrite(STDERR, sprintf("Merchant not found: %s\n", $options['merchant']));
    exit(1);
}

$chargeRepository = new MySqlChargeRepository($pdo);
$emailTransport = getenv('EMAIL_TRANSPORT') ?: 'smtp';

if ($emailTransport === 'file') 
{
    $emailSender = new FileEmailSender(__DIR__ . '/../storage/outbox');
} 
else 
{
    $emailSender = new SmtpEmailSender(
        host: getenv('SMTP_HOST') ?: 'mailpit',
        port: (int) (getenv('SMTP_PORT') ?: 1025),
        username: getenv('SMTP_USERNAME') ?: null,
        password: getenv('SMTP_PASSWORD') ?: null,
        encryption: getenv('SMTP_ENCRYPTION') ?: null,
        fromEmail: getenv('SMTP_FROM_EMAIL') ?: 'reports@example.com',
        fromName: getenv('SMTP_FROM_NAME') ?: 'Mock PSP Reports',
    );
}

$chargeReportService = new ChargeReportService($chargeRepository, $emailSender);

try 
{
    $deliveryTarget = $chargeReportService->sendMerchantReport($merchant, $from, $to);
} 
catch (Throwable $throwable) 
{
    fwrite(STDERR, sprintf("Failed to send charge report: %s\n", $throwable->getMessage()));
    exit(1);
}

fwrite(STDOUT, sprintf("Charge report sent via %s\n", $deliveryTarget));