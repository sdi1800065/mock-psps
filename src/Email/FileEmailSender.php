<?php

declare(strict_types=1);

namespace MockPsps\Email;

class FileEmailSender implements EmailSenderInterface
{
    public function __construct(private string $outboxDirectory){}

    public function send(string $to, string $subject, string $body): string
    {
        if (!is_dir($this->outboxDirectory) && !mkdir($concurrentDirectory = $this->outboxDirectory, 0777, true) && !is_dir($concurrentDirectory))
        {
            throw new \RuntimeException(sprintf('Failed to create outbox directory: %s', $this->outboxDirectory));
        }

        $filePath = sprintf(
            '%s/%s_%s.txt',
            rtrim($this->outboxDirectory, '/'),
            (new \DateTimeImmutable())->format('Ymd_His'),
            bin2hex(random_bytes(4))
        );

        $contents = sprintf("To: %s\nSubject: %s\n\n%s", $to, $subject, $body);

        if (file_put_contents($filePath, $contents) === false) 
        {
            throw new \RuntimeException(sprintf('Failed to write email file: %s', $filePath));
        }

        return $filePath;
    }
}