<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/** POST /contact_message (public, mailed to the admin). */
final readonly class ContactMessage
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name = '',
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public string $content = '',
    ) {
    }
}
