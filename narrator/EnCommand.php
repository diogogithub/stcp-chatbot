<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class EnCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'en';

    /**
     * @var string
     */
    protected $description = 'English Dictionary';

    /**
     * @var string
     */
    protected $usage = '/en';

    /**
     * @var string
     */
    protected $version = '0.1.0';

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
            'text'       => 'https://dictionary.cambridge.org/dictionary/english/' . urlencode(substr($message->getText(),4)),
        ];

        return Request::sendMessage($data);
    }
}
