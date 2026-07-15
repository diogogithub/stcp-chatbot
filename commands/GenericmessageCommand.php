<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\SystemCommands;

use Diogo\StcpTelegramBot\StcpClient;
use Diogo\StcpTelegramBot\TelegramReply;
use InvalidArgumentException;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use RuntimeException;

final class GenericmessageCommand extends SystemCommand
{
    protected $name = 'genericmessage';
    protected $description = 'Interpreta códigos de linha e paragem';
    protected $version = '1.0.0';
    protected $private_only = true;

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $input = trim($message->getText(true));

        if ($input === '') {
            return TelegramReply::send($message, 'Envie um código de paragem ou de linha.');
        }

        try {
            $client = new StcpClient();
            $reply = preg_match('/^(?:\\d{2,3}|(?:[1-9]|1[0-3])M|ZC)$/i', $input) === 1
                ? $client->lineInBothDirections($input)
                : $client->arrivalsAtStop($input);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $reply = $exception->getMessage();
        }

        return TelegramReply::send($message, $reply);
    }
}
