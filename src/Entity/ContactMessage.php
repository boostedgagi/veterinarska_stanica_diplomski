<?php

namespace App\Entity;

use App\ContextGroup;
use App\Repository\ContactMessageRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
class ContactMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactMessagesAsSender')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne(inversedBy: 'contactMessagesAsReceiver')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $receiver = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $chatId = null;


    /**
     * @param User|null $sender
     * @param User|null $receiver
     * @param string|null $content
     * @param string|null $status
     * @param string|null $chatId
     */
    public function __construct(
        ?User $sender,
        ?User $receiver,
        ?string $content,
        ?string $status,
        ?string $chatId
    )
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->content = $content;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->chatId = $chatId;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(
        [
            ContextGroup::CONTACT_MESSAGE_SENT
        ]
    )]
    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CONTACT_MESSAGE_SENT
        ]
    )]
    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): self
    {
        $this->receiver = $receiver;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CONTACT_MESSAGE_SENT
        ]
    )]
    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }


    #[Groups(
        [
            ContextGroup::CONTACT_MESSAGE_SENT
        ]
    )]
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CONTACT_MESSAGE_SENT
        ]
    )]
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    #[Groups(
        [
            ContextGroup::CONTACT_MESSAGE_SENT
        ]
    )]
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): self
    {
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    public function getChatId(): ?string
    {
        return $this->chatId;
    }

    public function setChatId(?string $chatId): static
    {
        $this->chatId = $chatId;

        return $this;
    }
}
