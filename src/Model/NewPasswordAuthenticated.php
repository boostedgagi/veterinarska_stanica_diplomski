<?php

namespace App\Model;

class NewPasswordAuthenticated
{
    private string $newPassword;

    /**
     * @return string
     */
    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    /**
     * @param string $newPassword
     * @return NewPasswordAuthenticated
     */
    public function setNewPassword(string $newPassword): NewPasswordAuthenticated
    {
        $this->newPassword = $newPassword;
        return $this;
    }


}