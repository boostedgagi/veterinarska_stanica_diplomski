<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/** POST /assign: the vet the current user picks. */
final readonly class AssignVet
{
    public function __construct(
        #[Assert\Positive]
        public int $vet = 0,
    ) {
    }
}
