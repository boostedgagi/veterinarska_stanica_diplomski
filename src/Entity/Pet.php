<?php

namespace App\Entity;

use App\ContextGroup;
use App\Repository\PetRepository;

//use DateTime;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Context;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\Date;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: PetRepository::class)]
class Pet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateOfBirth = null;

    #[ORM\Column(length: 255)]
    private ?string $animal = null;

    #[ORM\Column(length: 255)]
    private ?string $breed = null;

    #[ORM\ManyToOne(inversedBy: 'pets')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $owner = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'pet', targetEntity: HealthRecord::class, cascade: ['persist', 'remove'])]
    private Collection $healthRecords;

    public function __construct()
    {
        $this->healthRecords = new ArrayCollection();
    }

    #[Groups(
        [
            ContextGroup::SHOW_HEALTH_RECORD,
            ContextGroup::CREATE_PET,
            ContextGroup::SHOW_USER_PETS,
            ContextGroup::SHOW_VET
        ]
    )]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(
        [
            ContextGroup::SHOW_HEALTH_RECORD,
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::CREATE_PET,
            ContextGroup::SHOW_PET,
            ContextGroup::SHOW_USER_PETS,
            ContextGroup::FOUND_PET,
            ContextGroup::SHOW_VET
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
            ContextGroup::SHOW_HEALTH_RECORD,
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::CREATE_PET,
            ContextGroup::SHOW_PET,
            ContextGroup::SHOW_USER_PETS,
            ContextGroup::FOUND_PET,
            ContextGroup::SHOW_VET

        ]
    )]
    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(\DateTimeImmutable $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::SHOW_HEALTH_RECORD,
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::CREATE_PET,
            ContextGroup::SHOW_PET,
            ContextGroup::SHOW_USER_PETS,
            ContextGroup::SHOW_VET
        ]
    )]
    public function getAnimal(): ?string
    {
        return $this->animal;
    }

    public function setAnimal(string $animal): self
    {
        $this->animal = $animal;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::SHOW_HEALTH_RECORD,
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::CREATE_PET,
            ContextGroup::SHOW_PET,
            ContextGroup::SHOW_USER_PETS,
            ContextGroup::SHOW_VET
        ]
    )]
    public function getBreed(): ?string
    {
        return $this->breed;
    }

    public function setBreed(string $breed): self
    {
        $this->breed = $breed;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_PET,
            ContextGroup::SHOW_PET,
            ContextGroup::FOUND_PET,
//            ContextGroup::SHOW_VET
        ]
    )]
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_PET,
            ContextGroup::SHOW_USER_PETS,
            ContextGroup::SHOW_PET,
            ContextGroup::SHOW_VET
        ]
    )]
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
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
            ContextGroup::CREATE_PET,
            ContextGroup::SHOW_PET,
            ContextGroup::SHOW_VET
        ]
    )]
    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

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
            $healthRecord->setPet($this);
        }

        return $this;
    }

    public function removeHealthRecord(HealthRecord $healthRecord): self
    {
        if ($this->healthRecords->removeElement($healthRecord)) {
            // set the owning side to null (unless already changed)
            if ($healthRecord->getPet() === $this) {
                $healthRecord->setPet(null);
            }
        }

        return $this;
    }
}
