<?php

namespace App\Tests\Functional;

use App\Entity\Token;
use App\Entity\User;

/** Sign up → welcome mail with a verification token → verify the account. */
class RegistrationFlowTest extends ApiTestCase
{
    public function testRegisterAndVerify(): void
    {
        [$status, $body] = $this->api(null, 'POST', '/user', [
            'email' => 'new.owner@example.test', 'firstName' => 'New', 'lastName' => 'Owner',
            'password' => 'Secret-Pass-1', 'phone' => '0611111111',
        ]);

        self::assertSame(201, $status);
        self::assertArrayNotHasKey('password', $body, 'the hash is never returned');
        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', 'new.owner@example.test');

        $user = $this->reload(User::class, $body['id']);
        self::assertFalse($user->isVerified());
        self::assertNotSame('Secret-Pass-1', $user->getPassword(), 'stored hashed');

        $token = $this->em()->getRepository(Token::class)->findOneBy(['user' => $user]);
        self::assertNotNull($token, 'registration creates a verification token');

        [$status] = $this->api(null, 'GET', sprintf('/verify_account?token=%s&user_id=%d', $token->getToken(), $user->getId()));
        self::assertSame(200, $status);
        self::assertTrue($this->reload(User::class, $user->getId())->isVerified());
    }

    public function testInvalidRegistrationIsAValidationError(): void
    {
        [$status, $body] = $this->api(null, 'POST', '/user', ['email' => 'not-an-email']);

        self::assertSame(422, $status);
        self::assertNotEmpty($body['errors']);
        self::assertEmailCount(0);
    }
}
