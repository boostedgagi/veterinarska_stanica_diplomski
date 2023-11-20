<?php

namespace App\Enum;

enum HealthRecordStatus: string
{
    case ACTIVE = 'active';
    case WAITING = 'waiting';
    case CANCELED = 'canceled';
    case FINISHED = 'finished';

}
