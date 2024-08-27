<?php

namespace App\Model;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\Annotation\SerializedName;

class AssignVet
{
    private int $vet;

    /**
     * @return int
     */
    public function getVet(): int
    {
        return $this->vet;
    }

    /**
     * @param int $vet
     * @return AssignVet
     */
    public function setVet(int $vet): AssignVet
    {
        $this->vet = $vet;
        return $this;
    }



}