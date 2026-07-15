<?php

declare(strict_types=1);

use Diogo\StcpTelegramBot\BotFactory;
use Diogo\StcpTelegramBot\Config;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $config = Config::fromEnvironment();

    if ($config->webhookSecret !== null) {
        $providedSecret = (string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '');
        if ($providedSecret === '' || !hash_equals($config->webhookSecret, $providedSecret)) {
            http_response_code(403);
            exit;
        }
    }

    BotFactory::create($config)->handle();
    http_response_code(200);
} catch (Throwable $exception) {
    error_log('[stcp-telegram-bot] ' . $exception->getMessage());
    http_response_code(500);
}
