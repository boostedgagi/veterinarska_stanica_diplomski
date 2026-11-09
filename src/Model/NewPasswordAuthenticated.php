<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/** POST /password/make_new_authenticated */
final readonly class NewPasswordAuthenticated
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public string $newPassword = '',
    ) {
    }
}
