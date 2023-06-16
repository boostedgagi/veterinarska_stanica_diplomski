<?php

namespace App\Model;

use App\Entity\User;

class CancelHealthRecord
{
    private string $cancelContent;

    private User $canceler;

    /**
     * @return string
     */
    public function getCancelContent(): string
    {
        return $this->cancelContent;
    }

    /**
     * @param string $cancelContent
     */
    public function setCancelContent(string $cancelContent): void
    {
        $this->cancelContent = $cancelContent;
    }

    /**
     * @return User
     */
    public function getCanceler(): User
    {
        return $this->canceler;
    }

    /**
     * @param User $canceler
     */
    public function setCanceler(User $canceler): void
    {
        $this->canceler = $canceler;
    }

    public static function getDenyCancelMessage():string
        {
        return 'Examination is impossible to cancel less than hour before of its start.';
        }
}