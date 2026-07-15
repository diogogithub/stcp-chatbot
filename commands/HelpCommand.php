<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;

final class HelpCommand extends UserCommand
{
    protected $name = 'help';
    protected $description = 'Mostra a ajuda';
    protected $usage = '/help';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        return $this->replyToChat(
            "Comandos disponíveis:\n\n"
            . "/paragem <código> — próximas passagens numa paragem\n"
            . "/linha <código> — paragens da linha nos dois sentidos\n"
            . "/help — esta ajuda\n\n"
            . "Também pode enviar diretamente um código de paragem ou de linha.\n\n"
            . 'Este é um projeto independente e não oficial da STCP.'
        );
    }
}
