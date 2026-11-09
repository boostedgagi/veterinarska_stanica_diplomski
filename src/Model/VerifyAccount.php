<?php

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/** GET /verify_account?token=…&user_id=… (link from the welcome mail; token_id / expires are ignored). */
final readonly class VerifyAccount
{
    public function __construct(
        #[Assert\NotBlank(message: 'Missing token.')]
        public string $token = '',
        #[SerializedName('user_id')]
        #[Assert\Positive(message: 'Missing user_id.')]
        public int $userId = 0,
    ) {
    }
}
