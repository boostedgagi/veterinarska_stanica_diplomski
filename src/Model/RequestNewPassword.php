<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/** POST /password/request_new */
final readonly class RequestNewPassword
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email = '',
    ) {
    }
}
