<?php

namespace App\Model;

class NewPasswordEnvelope
{
    public int $token_id;

    public string $token;

    public string $expires;

    public string $email;

    public string $password;
}