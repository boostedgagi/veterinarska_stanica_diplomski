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

    public ?string $chatId = null;

    public ?DateTimeImmutable $createdAt = null;

    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     * @return Message
     */
    public function setSender(string $sender): self
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
    public function setReceiver(string $receiver): self
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
    public function setContent(string $content): self
    {
        $this->content = $content;
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
    public function setCreatedAt(): self
    {
        $this->createdAt = new DateTimeImmutable();
        return $this;
    }

    /**
     * @return string|null
     */
    public function getChatId(): ?string
    {
        return $this->chatId;
    }

    /**
     * @param string $chatId
     * @return Message
     */
    public function setChatId(string $chatId): self
    {
        $this->chatId = $chatId;
        return $this;
    }



}