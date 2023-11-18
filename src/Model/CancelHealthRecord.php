<?php

namespace App\Model;

use App\Entity\User;

class CancelHealthRecord
{
    private string $cancelMessage;

    private User $canceler;

    /**
     * @return string
     */
    public function getCancelMessage(): string
    {
        return $this->cancelMessage;
    }

    /**
     * @param string $cancelMessage
     */
    public function setCancelMessage(string $cancelMessage): void
    {
        $this->cancelMessage = $cancelMessage;
    }


    public function getCanceler(): User
    {
        return $this->canceler;
    }

    public function setCanceler(User $canceler): self
    {
        $this->canceler = $canceler;
    }

    public static function getDenyCancelMessage():string
    {
        return 'Examination is forbidden to cancel less than hour before of its start.';
    }

    public static function getSuccessfullCancelMessage():string
    {
        return 'Examination successfully canceled.';
    }


}