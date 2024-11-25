<?php

namespace App\Entity;

use App\ContextGroup;
use App\Repository\OnCallRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Context;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
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
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    public function __construct()
    {
    }

    #[Groups(
        [
            ContextGroup::ON_CALL
        ]
    )]
    public function getId(): ?int
    {
        return $this->id;
    }


    public function getVet(): ?User
    {
        return $this->vet;
    }

    public function setVet(?User $vet): self
    {
        $this->vet = $vet;

        return $this;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    #[ORM\PrePersist]
    public function prePersist(): self
    {
        $this->startedAt = new DateTimeImmutable();

        return $this;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeImmutable $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

}
