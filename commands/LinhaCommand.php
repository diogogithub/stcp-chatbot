<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\UserCommands;

use Diogo\StcpTelegramBot\StcpClient;
use Diogo\StcpTelegramBot\TelegramReply;
use InvalidArgumentException;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use RuntimeException;

final class LinhaCommand extends UserCommand
{
    protected $name = 'linha';
    protected $description = 'Mostra as paragens de uma linha nos dois sentidos';
    protected $usage = '/linha <código>';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        try {
            $reply = (new StcpClient())->lineInBothDirections($message->getText(true));
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $reply = $exception->getMessage();
        }

        return TelegramReply::send($message, $reply);
    }
}
