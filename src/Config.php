<?php

declare(strict_types=1);

namespace Diogo\StcpTelegramBot;

use RuntimeException;

final class Config
{
    /**
     * @param list<int> $adminIds
     */
    private function __construct(
        public readonly string $botToken,
        public readonly string $botUsername,
        public readonly array $adminIds,
        public readonly ?string $webhookUrl,
        public readonly ?string $webhookSecret,
    ) {
    }

    public static function fromEnvironment(): self
    {
        return new self(
            self::required('TELEGRAM_BOT_TOKEN'),
            self::required('TELEGRAM_BOT_USERNAME'),
            self::integerList('TELEGRAM_ADMIN_IDS'),
            self::optional('TELEGRAM_WEBHOOK_URL'),
            self::optional('TELEGRAM_WEBHOOK_SECRET'),
        );
    }

    private static function required(string $name): string
    {
        $value = self::optional($name);
        if ($value === null) {
            throw new RuntimeException("Missing required environment variable: {$name}");
        }

        return $value;
    }

    private static function optional(string $name): ?string
    {
        $value = trim((string) getenv($name));

        return $value === '' ? null : $value;
    }

    /**
     * @return list<int>
     */
    private static function integerList(string $name): array
    {
        $raw = self::optional($name);
        if ($raw === null) {
            return [];
        }

        $values = [];
        foreach (explode(',', $raw) as $item) {
            $item = trim($item);
            if ($item === '' || preg_match('/^\d+$/', $item) !== 1) {
                throw new RuntimeException("Invalid integer in environment variable: {$name}");
            }

            $values[] = (int) $item;
        }

        return array_values(array_unique($values));
    }
}
