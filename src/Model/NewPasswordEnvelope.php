<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * POST /password/make_new: the token from the reset mail, the account's e-mail and the new
 * password. (The frontend also sends token_id / expires / repeatPassword; they are ignored.)
 */
final readonly class NewPasswordEnvelope
{
    public function __construct(
        #[Assert\NotBlank]
        public string $token = '',
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email = '',
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public string $password = '',
    ) {
    }
}
