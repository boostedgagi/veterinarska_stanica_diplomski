<?php

namespace App\Model;

use App\Helper;

class Token
{
    private string $hashedToken;

    private string $expires;

    private string $emailAddress;

    /**
     * @param string $emailAddress
     */
    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function make30MinToken(): self
    {
        $this->hashedToken = md5(uniqid('', true) . mt_rand(10, 100));

        $this->expires = strtotime(date('Y-m-d h:i:s')) + (Helper::ONE_HOUR_IN_SECONDS / 2);

        return $this;
    }

}