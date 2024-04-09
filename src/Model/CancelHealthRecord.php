<?php

namespace App\Model;

use App\ContextGroup;
use App\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

class CancelHealthRecord
{
    private string $cancelMessage;

    public function getCancelMessage(): string
    {
        return $this->cancelMessage;
    }

    public function setCancelMessage(string $cancelMessage): self
    {
        $this->cancelMessage = $cancelMessage;

        return $this;
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