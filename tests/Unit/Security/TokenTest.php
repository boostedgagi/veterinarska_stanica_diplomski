<?php

namespace App\Tests\Unit\Security;

use App\Entity\Token;
use App\Entity\User;
use App\Factory\TokenFactory;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Account verification and password reset tokens only work for the account they were issued for.
 * Before, any valid token (e.g. from your own reset mail) could verify or reset ANY account.
 */
class TokenTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;
    private User $attacker;
    private User $victim;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->attacker = UserFactory::createOne(['typeOfUser' => User::TYPE_USER, 'allowed' => true, 'verified' => false]);
        $this->victim = UserFactory::createOne(['typeOfUser' => User::TYPE_USER, 'allowed' => true, 'verified' => false]);
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function tokenFor(User $user, bool $expired = false): Token
    {
        $token = new TokenFactory($this->em())->save($this->em()->find(User::class, $user->getId()));
        if ($expired) {
            $token->setExpires((string) (time() - 60));
            $this->em()->flush();
        }

        return $token;
    }

    private function reload(User $user): User
    {
        $this->em()->clear();

        return $this->em()->find(User::class, $user->getId());
    }

    private function verify(Token $token, User $user): int
    {
        $this->client->request('GET', sprintf('/verify_account?token_id=%d&token=%s&user_id=%d', $token->getId(), $token->getToken(), $user->getId()));

        return $this->client->getResponse()->getStatusCode();
    }

    private function resetPassword(Token $token, User $user, string $password): string
    {
        $this->client->request('POST', '/password/make_new', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'token_id' => $token->getId(), 'token' => $token->getToken(), 'expires' => $token->getExpires(),
            'email' => $user->getEmail(), 'password' => $password,
        ]));

        return (string) $this->client->getResponse()->getContent();
    }

    private function passwordIs(User $user, string $password): bool
    {
        return static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($this->reload($user), $password);
    }

    public function testTokensAreRandomAndBoundToTheUser(): void
    {
        $token = $this->tokenFor($this->attacker);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->getToken());
        self::assertSame($this->attacker->getId(), $token->getUser()?->getId());
        self::assertGreaterThan(time(), (int) $token->getExpires());
    }

    public function testVerifyAccountOnlyForTheTokensOwner(): void
    {
        $token = $this->tokenFor($this->attacker);

        self::assertSame(403, $this->verify($token, $this->victim));
        self::assertFalse($this->reload($this->victim)->isVerified());

        self::assertSame(200, $this->verify($token, $this->attacker));
        self::assertTrue($this->reload($this->attacker)->isVerified());
    }

    public function testVerifyAccountRejectsExpiredTokensAndMissingParameters(): void
    {
        self::assertSame(403, $this->verify($this->tokenFor($this->victim, expired: true), $this->victim));
        self::assertFalse($this->reload($this->victim)->isVerified());

        $this->client->request('GET', '/verify_account');
        self::assertResponseStatusCodeSame(400);
    }

    public function testPasswordResetOnlyForTheTokensOwner(): void
    {
        $token = $this->tokenFor($this->attacker);

        self::assertStringContainsString('Token is not valid', $this->resetPassword($token, $this->victim, 'hijacked-123'));
        self::assertFalse($this->passwordIs($this->victim, 'hijacked-123'), 'account takeover');

        $this->resetPassword($token, $this->attacker, 'new-password-123');
        self::assertTrue($this->passwordIs($this->attacker, 'new-password-123'));
    }

    public function testPasswordResetRejectsExpiredTokens(): void
    {
        $this->resetPassword($this->tokenFor($this->victim, expired: true), $this->victim, 'too-late-123');
        self::assertFalse($this->passwordIs($this->victim, 'too-late-123'));
    }
}
