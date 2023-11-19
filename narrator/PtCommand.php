<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class PtCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'pt';

    /**
     * @var string
     */
    protected $description = 'Dicionário de Português';

    /**
     * @var string
     */
    protected $usage = '/pt';

    /**
     * @var string
     */
    protected $version = '1.0.1';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $data = [
            'chat_id'    => $chat_id,
            'text'       => 'https://dicionario.priberam.org/' . urlencode(substr($message->getText(),4)),
        ];

        return Request::sendMessage($data);
    }
}
