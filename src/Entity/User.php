<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

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
    #[Groups(
        [
            'user_created',
            'user_showAll'
        ]
    )]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(
        [
            'user_created',
            'user_showAll',
            'pet_showAll',
            'pet_foundPet',
            'pet_created',
            'healthRecord_created',
            'healthRecord_showAll',
            'vet_nearby'
        ]
    )]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
//    #[Groups(
//        [
//            'user_created'
//        ]
//    )]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(
        [
            'user_created',
            'user_showAll',
            'pet_showAll',
            'pet_created',
            'healthRecord_created',
            'healthRecord_showAll',
            'vet_nearby'
        ]
    )]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(
        [
            'user_created',
            'user_showAll',
            'pet_showAll',
            'pet_created',
            'healthRecord_created',
            'healthRecord_showAll',
            'vet_nearby'
        ]
    )]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?bool $allowed = null;

    #[ORM\Column]
    #[Groups(
        [
            'user_created',
            'user_showAll'
        ]
    )]
    private ?int $typeOfUser = null;

    #[ORM\Column]
    #[Groups(
        [
            'user_showAll'
        ]
    )]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(
        [
            'user_showAll'
        ]
    )]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(
        [
            'user_showAll'
        ]
    )]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Pet::class, cascade: ['persist', 'remove'])]
    private Collection $pets;

    #[ORM\OneToMany(mappedBy: 'vet', targetEntity: HealthRecord::class)]
    private Collection $healthRecords;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(
        [
            'vet_nearby'
        ]
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[Groups(['user_showAll'])]
    private ?string $popularity;

    #[ORM\ManyToOne(targetEntity: self::class, cascade: ['persist'], inversedBy: 'users')]
    private ?self $vet = null;

    #[ORM\OneToMany(mappedBy: 'vet', targetEntity: self::class)]
    private Collection $users;

    private ?string $plainPassword = null;

    public function __construct()
    {
        $this->pets = new ArrayCollection();
        $this->healthRecords = new ArrayCollection();
        $this->users = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

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
     * @see UserInterface
     */
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

    public function setPassword(string $password): self
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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function isAllowed(): ?bool
    {
        return $this->allowed;
    }

    public function setAllowed(bool $allowed): self
    {
        $this->allowed = $allowed;

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

    public function getTypeOfUser(): ?int
    {
        return $this->typeOfUser;
    }

    public function setTypeOfUser(int $typeOfUser): self
    {
        $this->typeOfUser = $typeOfUser;

        return $this;
    }

    public function isVet():bool
    {
        return $this->getTypeOfUser() === 2;
    }

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
     * @return Collection<int, Pet>
     */
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }



    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPopularity(): ?string
    {
        return $this->popularity;
    }

    /**
     * @param string|null $popularity
     */
    public function setPopularity(?string $popularity): void
    {
        $this->popularity = $popularity;
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

    private function isVetSet(?User $vet):null|User
    {
        if($vet){
            return $vet;
        }
        return null;
    }

    public function getClients(): Collection
    {
        return $this->users;
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

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getPlainPassword():string|null
    {
        return $this->plainPassword;
    }
}
