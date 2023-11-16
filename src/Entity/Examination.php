<?php

namespace App\Entity;

use App\ContextGroup;
use App\Repository\ExaminationRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ExaminationRepository::class)]
class Examination
{
    private const ONE_HOUR_IN_MINUTES = 60;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\Column]
    private ?int $price = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'examination', targetEntity: HealthRecord::class)]
    private Collection $healthRecords;

    public function __construct()
    {
        $this->healthRecords = new ArrayCollection();
    }

    #[Groups(
        [
            ContextGroup::CREATE_EXAMINATION,
            ContextGroup::SHOW_EXAMINATION,
            'healthRecord_showAll'
        ]
    )]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(
        [
            ContextGroup::CREATE_EXAMINATION,
            ContextGroup::SHOW_EXAMINATION,
            ContextGroup::CREATE_HEALTH_RECORD,
            'healthRecord_showAll'
        ]
    )]
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_EXAMINATION,
            ContextGroup::SHOW_EXAMINATION,
            ContextGroup::CREATE_HEALTH_RECORD,
            'healthRecord_showAll'
        ]
    )]
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_EXAMINATION,
            ContextGroup::SHOW_EXAMINATION,
            ContextGroup::CREATE_HEALTH_RECORD,
            'healthRecord_showAll'
        ]
    )]
    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_EXAMINATION,
            ContextGroup::SHOW_EXAMINATION
        ]
    )]

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[Groups(
        [
            ContextGroup::SHOW_EXAMINATION
        ]
    )]
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, HealthRecord>
     */
    public function getHealthRecords(): Collection
    {
        return $this->healthRecords;
    }

    public function addHealthRecord(HealthRecord $healthRecord): self
    {
        if (!$this->healthRecords->contains($healthRecord)) {
            $this->healthRecords->add($healthRecord);
            $healthRecord->setExamination($this);
        }

        return $this;
    }

    public function removeHealthRecord(HealthRecord $healthRecord): self
    {
        if ($this->healthRecords->removeElement($healthRecord)) {
            // set the owning side to null (unless already changed)
            if ($healthRecord->getExamination() === $this) {
                $healthRecord->setExamination(null);
            }
        }

        return $this;
    }

    public function descriptiveLength(): bool
    {
        if ($this->getDuration() > 60)
            return 'Long';
        if ($this->getDuration() > 30)
            return 'Medium';
        if ($this->getDuration() > 15)
            return 'Short';

        return 'Mini';
    }
}
