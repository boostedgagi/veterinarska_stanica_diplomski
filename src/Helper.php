<?php

namespace App;

use App\Entity\User;
use DateTime;
use Exception;

class Helper
{
    public const ONE_HOUR_IN_SECONDS = 3600;

    public static function getNotificationMessageIfVetIsOccupied(User $personalVet, array $freeVets): string
    {
        if (!in_array($personalVet, $freeVets, true)) {
            return 'Your vet is occupied in this period of time, try to choose different time period.';
        }
        return 'Your vet is free in chosen time range and you can reserve him.';
    }

    public static function getVetNoAssignedMessage(): string
    {
        return 'You haven\'t chosen a vet yet.';
    }

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