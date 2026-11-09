<?php

namespace App\Exception;

/** A verification / password-reset token that is unknown, for another account, or expired. */
final class InvalidTokenException extends \DomainException
{
}
