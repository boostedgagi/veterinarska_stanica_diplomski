<?php

namespace App;

use App\Entity\User;

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
        return 'You don\'t assigned any veterinarian.';
    }
}