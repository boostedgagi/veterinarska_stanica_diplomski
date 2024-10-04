<?php

namespace App\Chat;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class Chat implements MessageComponentInterface
{

    function onOpen(ConnectionInterface $conn)
    {
        // TODO: Implement onOpen() method.
    }

    function onClose(ConnectionInterface $conn)
    {
        // TODO: Implement onClose() method.
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        // TODO: Implement onError() method.
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg)
    {
        // TODO: Implement onMessage() method.
    }
}