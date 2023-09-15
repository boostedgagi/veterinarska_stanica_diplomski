<?php

namespace App\Entity;

use App\Repository\OnCallRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnCallRepository::class)]
class OnCall
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'onCalls')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $vet = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column]
    private ?int $chatCount = null;

    /**
     * @param int|null $chatCount
     */
    public function __construct()
    {
        $this->chatCount = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVet(): ?User
    {
        return $this->vet;
    }

    public function setVet(?User $vet): static
    {
        $this->vet = $vet;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getChatCount(): ?int
    {
        return $this->chatCount;
    }

    public function setChatCount(int $chatCount): static
    {
        $this->chatCount = $chatCount;

        return $this;
    }
}
