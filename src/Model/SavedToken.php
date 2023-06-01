<?php

namespace App\Model;

class SavedToken
{
    public string $token;

    public string $expires;

    public function resolveAndFillVariable($variableName, $value): void
    {
        $this->{$variableName} = $value;
    }
}