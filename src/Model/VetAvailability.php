<?php

namespace App\Model;

use Symfony\Component\Serializer\Attribute\Groups;
use App\ContextGroup;
use App\Entity\User;

class VetAvailability
{
    #[Groups(
        [
            ContextGroup::SHOW_USER
        ]
    )]
    private User $vet;

    #[Groups(
        [
            ContextGroup::SHOW_USER
        ]
    )]
    private bool $available;

    /**
     * @return User
     */
    public function getVet(): User
    {
        return $this->vet;
    }

    /**
     * @param User $vet
     * @return VetAvailability
     */
    public function setVet(User $vet): VetAvailability
    {
        $this->vet = $vet;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * @param bool $available
     * @return VetAvailability
     */
    public function setAvailable(bool $available): VetAvailability
    {
        $this->available = $available;
        return $this;
    }


}