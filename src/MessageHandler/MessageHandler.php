<?php
namespace App\MessageHandler;

use App\Message\Message;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class MessageHandler
{
    public function __invoke(Message $message): void
    {
        dump($message);
    }
}