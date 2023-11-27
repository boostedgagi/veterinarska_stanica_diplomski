<?php

namespace App\Enum;

enum ContactMessageStatus:string
{
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case SEEN = 'seen';
}
