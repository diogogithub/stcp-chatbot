<?php

declare(strict_types=1);

namespace Diogo\StcpTelegramBot;

use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

final class TelegramReply
{
    /**
     * @param string|list<string> $messages
     */
    public static function send(Message $message, string|array $messages): ServerResponse
    {
        $responses = [];
        $items = is_array($messages) ? $messages : [$messages];

        foreach ($items as $item) {
            foreach (self::splitText(trim($item)) as $chunk) {
                $responses[] = Request::sendMessage([
                    'chat_id' => $message->getChat()->getId(),
                    'text' => $chunk,
                    'disable_web_page_preview' => true,
                ]);
            }
        }

        if ($responses === []) {
            return Request::sendMessage([
                'chat_id' => $message->getChat()->getId(),
                'text' => 'Não existem dados disponíveis.',
            ]);
        }

        return $responses[array_key_last($responses)];
    }

    /** @return list<string> */
    private static function splitText(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $chunks = [];
        while (strlen($text) > 3800) {
            $window = substr($text, 0, 3800);
            $cut = strrpos($window, PHP_EOL);
            if ($cut === false || $cut < 1000) {
                $cut = 3800;
                while ($cut > 0 && preg_match('//u', substr($text, 0, $cut)) !== 1) {
                    --$cut;
                }
            }

            $chunks[] = rtrim(substr($text, 0, $cut));
            $text = ltrim(substr($text, $cut));
        }

        if ($text !== '') {
            $chunks[] = $text;
        }

        return $chunks;
    }
}
