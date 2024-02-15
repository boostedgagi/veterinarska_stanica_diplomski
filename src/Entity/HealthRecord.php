<?php

namespace App\Entity;

use App\ContextGroup;
use App\Repository\HealthRecordRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use OpenApi\Context;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: HealthRecordRepository::class)]
class HealthRecord
{
    public const STATUS_CANCELED = 'canceled';
    public const ONE_MINUTE_IN_SECONDS = 60;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'healthRecords')]
    #[ORM\JoinColumn(nullable: true,onDelete: 'SET NULL')]
    private User $vet;


    #[ORM\ManyToOne(inversedBy: 'healthRecords')]
    #[ORM\JoinColumn(nullable: true,onDelete: 'SET NULL')]
    private ?Pet $pet = null;

    #[ORM\ManyToOne(inversedBy: 'healthRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Examination $examination = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $finishedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 64)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column()]
    private ?bool $notifiedWeekBefore;

    #[ORM\Column]
    private bool $madeByVet;

    #[ORM\Column()]
    private ?bool $notifiedDayBefore;

    private ?bool $atPresent = null;

    public function __construct()
    {
        $this->notifiedWeekBefore = false;
        $this->notifiedDayBefore = false;
    }

    #[Groups(
        [
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD
        ]
    )]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(
        [
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD
        ]
    )]
    public function getPet(): ?Pet
    {
        return $this->pet;
    }

    public function setPet(?Pet $pet): self
    {
        $this->pet = $pet;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD
        ]
    )]
    public function getExamination(): ?Examination
    {
        return $this->examination;
    }

    public function setExamination(?Examination $examination): self
    {
        $this->examination = $examination;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD
        ]
    )]
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD
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
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD
        ]
    )]
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[Groups(
        [
            ContextGroup::SHOW_HEALTH_RECORD
        ]
    )]
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD
        ]
    )]
    public function getVet(): ?User
    {
        return $this->vet;
    }

    public function setVet(?User $vet): self
    {
        $this->vet = $vet;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD
        ]
    )]
    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(DateTime $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD
        ]
    )]
    public function getFinishedAt(): ?DateTime
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(DateTime $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
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

    /**
     * @throws Exception
     * @return HealthRecord
     */
    public function makeHealthRecordNow(): self
    {
        $this
            ->setMadeByVet(true)
            ->setStartedAt(new DateTime())
            ->setStatus('active');

        $examDurationInSeconds = $this->getExamination()->getDuration() * self::ONE_MINUTE_IN_SECONDS;

        $this->setFinishedAt(new DateTime('+' . $examDurationInSeconds . 'seconds'));

        return $this;
    }
}
