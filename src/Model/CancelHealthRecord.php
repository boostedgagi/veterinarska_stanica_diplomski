<?php

namespace App\Model;

use App\Entity\User;

class CancelHealthRecord
{
    private string $cancelContent;

    private int $canceler;

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


    public function getCanceler(): int
    {
        return $this->canceler;
    }


    public function setCanceler(int $canceler): void
    {
        $this->canceler = $canceler;
    }

    public static function getDenyCancelMessage():string
        {
        return 'Examination is impossible to cancel less than hour before of its start.';
        }
}