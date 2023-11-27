<?php

namespace App\Entity;

use App\ContextGroup;
use App\Repository\HealthRecordRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const TYPE_ADMIN = 1;
    public const TYPE_VET = 2;
    public const TYPE_USER = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?bool $allowed = null;

    #[ORM\Column]
    private ?int $typeOfUser = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Pet::class, cascade: ['persist', 'remove'])]
    private Collection $pets;

    #[ORM\OneToMany(mappedBy: 'vet', targetEntity: HealthRecord::class)]
    private Collection $healthRecords;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\ManyToOne(targetEntity: self::class, cascade: ['persist'], inversedBy: 'users')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?self $vet = null;

    #[ORM\OneToMany(mappedBy: 'vet', targetEntity: self::class)]
    private Collection $users;

    private ?string $plainPassword = null;

    #[ORM\OneToMany(mappedBy: 'sender', targetEntity: ContactMessage::class)]
    private Collection $contactMessagesAsSender;

    #[ORM\OneToMany(mappedBy: 'receiver', targetEntity: ContactMessage::class)]
    private Collection $contactMessagesAsReceiver;

    #[ORM\OneToMany(mappedBy: 'vet', targetEntity: OnCall::class)]
    private Collection $onCalls;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $popularity = null;

    public function __construct()
    {
        $this->pets = new ArrayCollection();
        $this->healthRecords = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->contactMessagesAsSender = new ArrayCollection();
        $this->contactMessagesAsReceiver = new ArrayCollection();
        $this->onCalls = new ArrayCollection();
        $this->setAllowed(false);
        $this->setTypeOfUser(3);
    }

    #[Groups(
        [
            ContextGroup::CREATE_USER,
            ContextGroup::SHOW_USER,
            ContextGroup::SHOW_HEALTH_RECORD
        ]
    )]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(
        [
            ContextGroup::CREATE_USER,
            ContextGroup::SHOW_USER,
            ContextGroup::SHOW_PET,
            ContextGroup::FOUND_PET,
            ContextGroup::CREATE_PET,
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD,
            ContextGroup::SHOW_NEARBY_VETS,
        ]
    )]
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @return array|string[]
     */
    #[Groups(
        [
            ContextGroup::CREATE_USER,
            ContextGroup::SHOW_USER,
        ]
    )]
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(null|string $password): self
    {

        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    #[Groups(
        [
            ContextGroup::CREATE_USER,
            ContextGroup::SHOW_USER,
            ContextGroup::SHOW_PET,
            ContextGroup::CREATE_PET,
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD,
            ContextGroup::SHOW_NEARBY_VETS
        ]
    )]
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::CREATE_USER,
            ContextGroup::SHOW_USER,
            ContextGroup::SHOW_PET,
            ContextGroup::CREATE_PET,
            ContextGroup::CREATE_HEALTH_RECORD,
            ContextGroup::SHOW_HEALTH_RECORD,
            ContextGroup::SHOW_NEARBY_VETS
        ]
    )]
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::SHOW_USER
        ]
    )]
    public function isAllowed(): ?bool
    {
        return $this->allowed;
    }

    public function setAllowed(bool $allowed): self
    {
        $this->allowed = $allowed;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::SHOW_USER
        ]
    )]
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::SHOW_USER
        ]
    )]
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isVet(): bool
    {
        return $this->getTypeOfUser() === 2;
    }

    #[Groups(
        [
            ContextGroup::CREATE_USER,
            ContextGroup::SHOW_USER
        ]
    )]
    public function getTypeOfUser(): ?int
    {
        return $this->typeOfUser;
    }

    public function setTypeOfUser(int $typeOfUser): self
    {
        $this->typeOfUser = $typeOfUser;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::SHOW_USER
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


    #[Groups(
        [
            'pet_showByUser'
        ]
    )]
    public function getPets(): Collection
    {
        return $this->pets;
    }

    public function addPet(Pet $pet): self
    {
        if (!$this->pets->contains($pet)) {
            $this->pets->add($pet);
            $pet->setOwner($this);
        }

        return $this;
    }

    public function removePet(Pet $pet): self
    {
        if ($this->pets->removeElement($pet)) {
            // set the owning side to null (unless already changed)
            if ($pet->getOwner() === $this) {
                $pet->setOwner(null);
            }
        }

        return $this;
    }

    public function getHealthRecord(): ?HealthRecord
    {
        return $this->healthRecord;
    }

    public function setHealthRecord(?HealthRecord $healthRecord): self
    {
        $this->healthRecord = $healthRecord;

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
            $healthRecord->setVet($this);
        }

        return $this;
    }

    public function removeHealthRecord(HealthRecord $healthRecord): self
    {
        if ($this->healthRecords->removeElement($healthRecord)) {
            // set the owning side to null (unless already changed)
            if ($healthRecord->getVet() === $this) {
                $healthRecord->setVet(null);
            }
        }

        return $this;
    }

    public function getVet(): ?self
    {
        return $this->vet;
    }

    public function setVet(null|User $vet): self
    {
        $this->vet = $this->isVetSet($vet);

        return $this;
    }

    #[Groups(
        [
            ContextGroup::SHOW_NEARBY_VETS,
            ContextGroup::SHOW_USER,
        ]
    )]
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::SHOW_USER,
        ]
    )]
    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    #[Groups(
        [
            ContextGroup::SHOW_USER,
        ]
    )]
    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getClients(): Collection
    {
        return $this->users;
    }

    public function removeUser(self $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getVet() === $this) {
//                $user->setVet();
            }
        }

        return $this;
    }

//    public function addUser(self $user): self
//    {
//        if (!$this->users->contains($user)) {
//            $this->users->add($user);
//            $user->setVet($this);
//        }
//
//        return $this;
//    }

    public function getPlainPassword(): string|null
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @return Collection<int, ContactMessage>
     */
    public function getContactMessagesAsSender(): Collection
    {
        return $this->contactMessagesAsSender;
    }

    public function addContactMessagesAsSender(ContactMessage $contactMessagesAsSender): static
    {
        if (!$this->contactMessagesAsSender->contains($contactMessagesAsSender)) {
            $this->contactMessagesAsSender->add($contactMessagesAsSender);
            $contactMessagesAsSender->setSender($this);
        }

        return $this;
    }

    public function removeContactMessagesAsSender(ContactMessage $contactMessagesAsSender): static
    {
        if ($this->contactMessagesAsSender->removeElement($contactMessagesAsSender)) {
            // set the owning side to null (unless already changed)
            if ($contactMessagesAsSender->getSender() === $this) {
                $contactMessagesAsSender->setSender(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ContactMessage>
     */
    public function getContactMessagesAsReceiver(): Collection
    {
        return $this->contactMessagesAsReceiver;
    }

    public function addContactMessagesAsReceiver(ContactMessage $contactMessagesAsReceiver): static
    {
        if (!$this->contactMessagesAsReceiver->contains($contactMessagesAsReceiver)) {
            $this->contactMessagesAsReceiver->add($contactMessagesAsReceiver);
            $contactMessagesAsReceiver->setReceiver($this);
        }

        return $this;
    }

    public function removeContactMessagesAsReceiver(ContactMessage $contactMessagesAsReceiver): static
    {
        if ($this->contactMessagesAsReceiver->removeElement($contactMessagesAsReceiver)) {
            // set the owning side to null (unless already changed)
            if ($contactMessagesAsReceiver->getReceiver() === $this) {
                $contactMessagesAsReceiver->setReceiver(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OnCall>
     */
    public function getOnCalls(): Collection
    {
        return $this->onCalls;
    }

    public function addOnCall(OnCall $onCall): static
    {
        if (!$this->onCalls->contains($onCall)) {
            $this->onCalls->add($onCall);
            $onCall->setVet($this);
        }

        return $this;
    }

    public function removeOnCall(OnCall $onCall): static
    {
        if ($this->onCalls->removeElement($onCall)) {
            // set the owning side to null (unless already changed)
            if ($onCall->getVet() === $this) {
                $onCall->setVet(null);
            }
        }

        return $this;
    }

    public function makeVet():self
    {
        $this->setRoles(["ROLE_VET"]);
        $this->setAllowed(true);
        $this->setTypeOfUser(2);

        return $this;
    }

    private function isVetSet(?User $vet): null|User
    {
        return $vet ?: null;
    }

    #[Groups(
        [
            ContextGroup::SHOW_USER
        ]
    )]
    public function getPopularity(): ?string
    {
        return $this->popularity;
    }

    public function setPopularity(?string $popularity): static
    {
        $this->popularity = $popularity;

        return $this;
    }
}
