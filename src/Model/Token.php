<?php

namespace App\Model;

use App\Helper;

class Token
{
    private string $token;

    private string $expires;

    private string $emailAddress;

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getExpires(): string
    {
        return $this->expires;
    }

    /**
     * @param string $expires
     */
    public function setExpires(string $expires): void
    {
        $this->expires = $expires;
    }

    /**
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    /**
     * @param string $emailAddress
     */
    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function make30MinToken(): self
    {
        $this->setToken(md5(uniqid('', true) . mt_rand(10, 100)));

        $this->setExpires(strtotime(date('Y-m-d h:i:s')) + (Helper::ONE_HOUR_IN_SECONDS / 2));

        return $this;
    }


}