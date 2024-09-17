<?php

namespace App\Model;

use App\ContextGroup;
use App\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

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