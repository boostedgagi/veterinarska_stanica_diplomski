<?php

namespace App\Enum;

enum NotifyingTimeRange:string
{
    case NEXT_DAY = "today";
    case NEXT_WEEK = "next_week";
}
