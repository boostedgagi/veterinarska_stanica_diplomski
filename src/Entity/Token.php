<?php

namespace App\Entity;

use App\Helper;
use App\Repository\TokenEntityRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Model\Token as ModelToken;
use Exception;

#[ORM\Entity(repositoryClass: TokenEntityRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $token = null;

    #[ORM\Column(length: 255)]
    private ?string $expires = null;

    public function __construct()
    {
        $this->token = md5(uniqid('', true) . random_int(10, 100));
        $this->expires = strtotime(date('Y-m-d h:i:s')) + (Helper::ONE_HOUR_IN_SECONDS / 2);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getExpires(): ?string
    {
        return $this->expires;
    }

    public function setExpires(string $expires): self
    {
        $this->expires = $expires;

        return $this;
    }
}
