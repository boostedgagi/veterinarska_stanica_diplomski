<?php

namespace App\Message;

enum MessageStatus : string
{
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case SEEN = 'seen';
    case DELETED = 'deleted';

//    public function status(): string
//    {
//        return match($this)
//        {
////            self::SEEN
//        };
//    }
}
