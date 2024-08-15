<?php

namespace App\Model;

class VerifyAccount
{
    private ?int $tokenId;
    private ?string $token;
    private ?string $expires;
    private ?int $userId;

    public function __construct(array $data)
    {
        $this->tokenId = $data['token_id'] ?? null;
        $this->token = $data['token'] ?? null;
        $this->expires = $data['expires'] ?? null;
        $this->userId = (int)($data['user_id'] ?? null);
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getTokenId(): ?int
    {
        return $this->tokenId;
    }

    public function getExpires(): ?int
    {
        return $this->expires;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

}