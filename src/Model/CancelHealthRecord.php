<?php

namespace App\Model;

use App\ContextGroup;
use App\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

class CancelHealthRecord
{
    public string $cancelMessage;

    public string $status;

    /**
     * @return string
     */
    public function getCancelMessage(): string
    {
        return $this->cancelMessage;
    }

    /**
     * @param string $cancelMessage
     * @return CancelHealthRecord
     */
    public function setCancelMessage(string $cancelMessage): CancelHealthRecord
    {
        $this->cancelMessage = $cancelMessage;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return CancelHealthRecord
     */
    public function setStatus(string $status): CancelHealthRecord
    {
        $this->status = $status;
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