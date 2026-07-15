<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\UserCommands;

use Diogo\StcpTelegramBot\StcpClient;
use Diogo\StcpTelegramBot\TelegramReply;
use InvalidArgumentException;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use RuntimeException;

final class ParagemCommand extends UserCommand
{
    protected $name = 'paragem';
    protected $description = 'Mostra as próximas passagens numa paragem';
    protected $usage = '/paragem <código>';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        try {
            $reply = (new StcpClient())->arrivalsAtStop($message->getText(true));
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $reply = $exception->getMessage();
        }

        return TelegramReply::send($message, $reply);
    }
}
