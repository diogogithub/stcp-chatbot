<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

/**
 * User "/image" command
 *
 * Fetch any uploaded image from the Uploads path.
 */
class EmentafcupCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'ementafcup';

    /**
     * @var string
     */
    protected $description = 'Ementa FCUP da Semana';

    /**
     * @var string
     */
    protected $usage = '/ementafcup';

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
        $restaurante_id = 1; // FCUP
        $message = $this->getMessage();
        $menu = json_decode(file_get_contents('http://admin.multirest.eu/api/weekly-menus?institution_id='.$restaurante_id), true);
        $result = '';

        foreach ($menu as $entry) {
            $institution_name = $entry['institution_name'];
            $year = $entry['year'];
            $week = $entry['week'];

            $result .= "$institution_name - Week {$week}\n";

            for ($day = 0; $day <= 5; ++$day, $dishes = $entry['dishes'][$day]) {
                $week_day = [
                    1 => '2.ª Feira',
                    2 => '3.ª Feira',
                    3 => '4.ª Feira',
                    4 => '5.ª Feira',
                    5 => '6.ª Feira',
                ][$day];

                $today = date('w');

                if ($today == $day) {
                    $result .= "*$week_day*\n"; // Bold
                } else {
                    $result .= "$week_day\n";
                }

                foreach ($dishes as $dish) {
                    $name = $dish['name'];
                    $type_name = $dish['type_name'];

                    $result .= "$type_name: $name\n";
                }

                $result .= "\n";
            }

            $result .= "\n";
        }

        return $this->replyToChat($result, [
            'parse_mode' => 'markdown',
        ]);
    }
}
