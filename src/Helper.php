<?php

namespace App;

use App\Entity\User;
use DateTime;
use Exception;

class Helper
{
    public const ONE_HOUR_IN_SECONDS = 3600;

    /**
     * @throws Exception
     */
    public static function makeHashedChatId(): string
    {
        $someRandomNumber=random_int(1000,9000);

        $now = new DateTime();
        $plainChatId = $someRandomNumber.
            strtotime(
                $now->format('Y/m/d H:i:s')
            );

        return hash('sha256', $plainChatId);
    }
}