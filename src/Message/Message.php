<?php
namespace App\Message;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping\PrePersist;

class Message
{
    public string $sender;

    public string $receiver;

    public string $content;

    public string $status;

    public ?DateTimeImmutable $createdAt = null;

    /**
     * @return User
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     * @return Message
     */
    public function setSender(string $sender): Message
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiver(): string
    {
        return $this->receiver;
    }

    /**
     * @param string $receiver
     * @return Message
     */
    public function setReceiver(string $receiver): Message
    {
        $this->receiver = $receiver;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return Message
     */
    public function setContent(string $content): Message
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Message
     */
    public function setStatus(string $status): Message
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Message
     */
    public function setCreatedAt(): Message
    {
        $this->createdAt = new DateTimeImmutable();
        return $this;
    }
}