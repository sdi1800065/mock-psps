<?php

declare(strict_types=1);

namespace MockPsps\Model;

class ApiKeyValidator
{
    public static function hash(string $apiKey): string
    {
        return password_hash($apiKey, PASSWORD_BCRYPT);
    }
    public static function verify(string $apiKey, string $hash): bool
    {
        return password_verify($apiKey, $hash);
    }

    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }
}
