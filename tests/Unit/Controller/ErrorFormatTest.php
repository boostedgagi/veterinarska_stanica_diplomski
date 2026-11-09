<?php

namespace App\Tests\Unit\Controller;

use App\Entity\User;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Every API error is {"code": <status>, "message": "..."} (+ "errors" for validation),
 * see App\EventListener\ApiExceptionListener.
 */
class ErrorFormatTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @param array<string, mixed>|null $json
     * @return array{int, array<string, mixed>}
     */
    private function call(?User $as, string $method, string $uri, ?array $json = null): array
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        if ($as) {
            $this->client->loginUser($as);
        }
        $this->client->request($method, $uri, server: ['CONTENT_TYPE' => 'application/json'], content: $json === null ? null : json_encode($json));
        $response = $this->client->getResponse();

        return [$response->getStatusCode(), json_decode((string) $response->getContent(), true) ?? []];
    }

    private function user(int $type = User::TYPE_USER): User
    {
        return UserFactory::createOne(['typeOfUser' => $type, 'allowed' => true, 'verified' => true]);
    }

    public function testErrorsShareOneShape(): void
    {
        $user = $this->user();
        $stranger = $this->user();

        foreach ([
            'no token' => [null, 'GET', '/me', 401],
            'not found (own message)' => [$user, 'GET', '/pet/999999', 404],
            'not found (entity argument)' => [$user, 'GET', '/user/999999', 404],
            'forbidden' => [$stranger, 'GET', '/user/' . $user->getId(), 403],
            'unknown route' => [$user, 'GET', '/no-such-route', 404],
            'bad request' => [null, 'GET', '/found_pet', 400],
        ] as $case => [$as, $method, $uri, $status]) {
            [$code, $body] = $this->call($as, $method, $uri);
            self::assertSame($status, $code, $case);
            self::assertSame($status, $body['code'] ?? null, $case);
            self::assertIsString($body['message'] ?? null, $case);
            self::assertStringNotContainsString('App\\', $body['message'], "$case: no internals in the message");
        }
    }

    public function testValidationErrorsListTheFields(): void
    {
        [$code, $body] = $this->call(null, 'POST', '/contact_message', ['name' => '', 'email' => 'not-an-email', 'content' => '']);

        self::assertSame(422, $code);
        self::assertSame(422, $body['code']);
        self::assertSame('Validation failed.', $body['message']);
        self::assertNotEmpty($body['errors']);
    }

    public function testRequestPayloadErrorsHaveTheSameShape(): void
    {
        [$code, $body] = $this->call($this->user(), 'POST', '/assign', ['vet' => 'not-a-number']);
        self::assertSame(422, $code);
        self::assertSame('Validation failed.', $body['message']);
        self::assertSame(['vet'], array_column($body['errors'], 'field'));

        [$code, $body] = $this->call(null, 'GET', '/verify_account?token=abc');
        self::assertSame(400, $code);
        self::assertSame(['userId'], array_column($body['errors'], 'field'));
    }

    public function testContactMailRepliesToTheVisitor(): void
    {
        $this->call(null, 'POST', '/contact_message', ['name' => 'Ana', 'email' => 'ana@example.test', 'content' => 'Hi']);

        self::assertResponseStatusCodeSame(204);
        $mail = self::getMailerMessage();
        self::assertEmailAddressContains($mail, 'Reply-To', 'ana@example.test');
        self::assertInstanceOf(Email::class, $mail);
        self::assertNotContains('ana@example.test', array_map(static fn ($a) => $a->getAddress(), $mail->getFrom()), 'was sent "from" the visitor');
    }

    public function testPasswordResetRequestDoesNotRevealAccounts(): void
    {
        $user = $this->user();

        [$known, $knownBody] = $this->call(null, 'POST', '/password/request_new', ['email' => $user->getEmail()]);
        [$unknown, $unknownBody] = $this->call(null, 'POST', '/password/request_new', ['email' => 'nobody@example.test']);

        self::assertSame(201, $known);
        self::assertSame([$known, $knownBody], [$unknown, $unknownBody], 'was 200 "User not found." for unknown e-mails');
    }

    public function testAssignOnlyAcceptsVets(): void
    {
        $user = $this->user();

        self::assertSame(400, $this->call($user, 'POST', '/assign', ['vet' => 999999])[0], 'was 201 and cleared the vet');
        self::assertSame(400, $this->call($user, 'POST', '/assign', ['vet' => $this->user()->getId()])[0], 'was 201 with a non-vet');
        self::assertSame(204, $this->call($user, 'POST', '/assign', ['vet' => $this->user(User::TYPE_VET)->getId()])[0]);
    }
}
