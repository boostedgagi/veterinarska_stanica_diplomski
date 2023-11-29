<?php

namespace App\Enum;

enum NotifyingTimeRange:string
{
    case NEXT_DAY = 'next_day';
    case NEXT_WEEK = 'next_week';
}
