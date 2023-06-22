<?php

namespace App\Entity;

use App\Repository\HealthRecordRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: HealthRecordRepository::class)]
class HealthRecord
{
    public const STATUS_CANCELED = 'canceled';
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(
        [
            'healthRecord_showAll'
        ]
    )]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'healthRecords')]
    #[Groups(
        [
            'healthRecord_created',
            'healthRecord_showAll'
        ]
    )]
    #[ORM\JoinColumn(nullable: true,onDelete: 'SET NULL')]
    private User $vet;


    #[ORM\ManyToOne(inversedBy: 'healthRecords')]
    #[Groups(
        [
            'healthRecord_created',
            'healthRecord_showAll'
        ]
    )]
    #[ORM\JoinColumn(nullable: true,onDelete: 'SET NULL')]
    private ?Pet $pet = null;

    #[ORM\ManyToOne(inversedBy: 'healthRecords')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(
        [
            'healthRecord_created',
            'healthRecord_showAll'
        ]
    )]
    private ?Examination $examination = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(
        [
            'healthRecord_created',
            'healthRecord_showAll'
        ]
    )]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(
        [
            'healthRecord_created',
            'healthRecord_showAll'
        ]
    )]
    private ?\DateTimeInterface $finishedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(
        [
            'healthRecord_created',
            'healthRecord_showAll'
        ]
    )]
    private ?string $comment = null;

    #[ORM\Column(length: 64)]
    #[Groups(
        [
            'healthRecord_created',
            'healthRecord_showAll'
        ]
    )]
    private ?string $status = null;

    #[ORM\Column]
    #[Groups(
        [
            'healthRecord_showAll'
        ]
    )]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    private ?bool $notifiedWeekBefore = null;

    #[ORM\Column]
    private bool $madeByVet;

    #[ORM\Column]
    private ?bool $notifiedDayBefore = null;

    private ?bool $atPresent = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPet(): ?Pet
    {
        return $this->pet;
    }

    public function setPet(?Pet $pet): self
    {
        $this->pet = $pet;

        return $this;
    }

    public function getExamination(): ?Examination
    {
        return $this->examination;
    }

    public function setExamination(?Examination $examination): self
    {
        $this->examination = $examination;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
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

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(\DateTimeInterface $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }


    public function isMadeByVet(): ?bool
    {
        return $this->madeByVet;
    }

    public function setMadeByVet(bool $madeByVet): self
    {
        $this->madeByVet = $madeByVet;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getNotifiedWeekBefore(): ?bool
    {
        return $this->notifiedWeekBefore;
    }

    /**
     * @param bool|null $notifiedWeekBefore
     */
    public function setNotifiedWeekBefore(?bool $notifiedWeekBefore): void
    {
        $this->notifiedWeekBefore = $notifiedWeekBefore;
    }

    public function isNotifiedDayBefore(): ?bool
    {
        return $this->notifiedDayBefore;
    }

    public function setNotifiedDayBefore(bool $notifiedDayBefore): self
    {
        $this->notifiedDayBefore = $notifiedDayBefore;

        return $this;
    }

    /**
     * @param string|null $atPresent
     */
    public function setAtPresent(?string $atPresent): void
    {
        $this->atPresent = $atPresent;
    }

    /**
     * @return string|null
     */
    public function getAtPresent(): ?string
    {
        return $this->atPresent;
    }

    public function checkHolyTrinity():bool{
        return $this->getVet() && $this->getPet() && $this->getExamination();
    }

}
