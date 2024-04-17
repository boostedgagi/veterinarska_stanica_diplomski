<?php

namespace App\Model;

use App\ContextGroup;
use Symfony\Component\Serializer\Annotation\Groups;

class FreeVetResponse
{
    #[Groups(
        [
            ContextGroup::SHOW_USER
        ]
    )]
    public array $freeVets;

    #[Groups(
        [
            ContextGroup::SHOW_USER
        ]
    )]
    public string $message;
}