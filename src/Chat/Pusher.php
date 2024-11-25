<?php

namespace App\Chat;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class Pusher implements WampServerInterface
{

    protected array $chatIds = [];

    public function onOpen(ConnectionInterface $conn)
    {
        // TODO: Implement onOpen() method.
    }


    public function onClose(ConnectionInterface $conn)
    {
        // TODO: Implement onClose() method.
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        // TODO: Implement onError() method.
    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();

    }

    public function onSubscribe(ConnectionInterface $conn, $topic): void
    {
        $this->chatIds[$topic->getId()] = $topic;
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        // TODO: Implement onUnSubscribe() method.
    }

    public function onBlogEntry($entry)
    {
        $entryData = json_decode($entry, true, 512, JSON_THROW_ON_ERROR);
        if (!array_key_exists($entryData['chatId'], $this->chatIds)) {
            return;
        }

        $chat=$this->chatIds[$entryData['chatId']];

        $chat->broadcast($entryData);
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        $conn->close();
    }
}