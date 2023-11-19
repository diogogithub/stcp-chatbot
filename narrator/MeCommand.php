<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

/**
 * User "/me" command
 *
 * IRC like me command.
 *
 */
class MeCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'me';

    /**
     * @var string
     */
    protected $description = 'The most important purpose of this bot, to act as a narrator - IRC styled.';

    /**
     * @var string
     */
    protected $usage = '/me <what you\'re doing or feeling';

    /**
     * @var string
     */
    protected $version = '0.1.0';

    /**
     * Guzzle Client object
     *
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $sender = $message->getFrom()->getFirstName() .' '. $message->getFrom()->getLastName();
        $chat_id  = $message->getChat()->getId();
        $status = $message->getText(true);

        $text = 'You must specify your status in format: /me <status>';

        if ($status !== '') {
            $text = "{$sender} {$status}";
        }

        $data = [
            'chat_id' => $chat_id,
        'parse_mode' => 'MARKDOWN',
            'text'    => $text,
        ];

    // Delete this editing reply message.
    Request::deleteMessage([
        'chat_id'    => $chat_id,
        'message_id' => $message->getMessageId(),
    ]);

        return Request::sendMessage($data);
    }
}
