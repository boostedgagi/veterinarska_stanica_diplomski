<?php

namespace App;

use Exception;

class Helper
{
    public const ONE_HOUR_IN_SECONDS = 3600;

    /**
     * @throws Exception
     */
    public static function makeHashedChatId(): string
    {
        // 64 hex chars like before, but random instead of time + 4-digit number (guessable)
        return bin2hex(random_bytes(32));
    }
}