<?php

declare(strict_types=1);

namespace MockPsps\Email;

interface EmailSenderInterface
{
    public function send(string $to, string $subject, string $body): string;
}