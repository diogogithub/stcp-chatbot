<?php

declare(strict_types=1);

namespace Diogo\StcpTelegramBot;

use Longman\TelegramBot\Telegram;

final class BotFactory
{
    public static function create(Config $config): Telegram
    {
        $telegram = new Telegram($config->botToken, $config->botUsername);
        $telegram->addCommandsPath(dirname(__DIR__) . '/commands');

        if ($config->adminIds !== []) {
            $telegram->enableAdmins($config->adminIds);
        }

        $telegram->enableLimiter();

        return $telegram;
    }
}
