<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contact form (public, mailed to the admin). Validated so a bad address is a 422,
 * not a 500 from the mailer.
 */
class ContactMessage
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 5000)]
    private ?string $content = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
