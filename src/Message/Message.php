<?php
namespace App\Message;

use App\Entity\User;
use DateTimeImmutable;

class Message
{
    public int $id;

    public User $sender;

    public User $receiver;

    public string $content;

    public string $status;

    public DateTimeImmutable $createdAt;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Message
     */
    public function setId(int $id): Message
    {
        $this->id = $id;
        return $this;
    }
}