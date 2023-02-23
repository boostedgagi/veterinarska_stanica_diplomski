<?php

namespace App\Service;

use App\Entity\User;

class UserService
{
    public function vetPopularity(User $vet, int $examinationsCount):string
    {
        $numberOfVetExaminations = count($vet->getHealthRecords());
        $percentage = 100 * $numberOfVetExaminations / $examinationsCount;

        return number_format((float)$percentage, 2, '.', '') . '%';
    }


}