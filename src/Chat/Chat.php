<?php

namespace App\Chat;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class Chat implements MessageComponentInterface
{
    protected SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $num = count($this->clients)-1;

        echo sprintf('Connection sending message "%s" to %d other connection%s' . "\n"
            ,$msg, $num, $num == 1 ? '' : 's');

        /**
         * @var $client SplObjectStorage
         */
        foreach ($this->clients as $client){
            if($conn!==$client){
                $client->send($msg);
            }
        }
    }
}