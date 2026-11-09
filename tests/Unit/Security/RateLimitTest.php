<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Public routes are limited per client IP (config/packages/rate_limiter.yaml), logins per
 * e-mail + IP (security.yaml login_throttling). In tests the counters live in an array cache,
 * so the client keeps one kernel (disableReboot) to keep counting.
 */
class RateLimitTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
    }

    /** @param array<string, mixed> $json */
    private function post(string $uri, array $json, string $ip = '203.0.113.7'): int
    {
        $this->client->request('POST', $uri, server: ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $ip], content: json_encode($json));

        return $this->client->getResponse()->getStatusCode();
    }

    public function testContactFormIsLimitedPerIp(): void
    {
        $message = ['name' => 'Ana', 'email' => 'ana@example.test', 'content' => 'Hello'];

        for ($i = 1; $i <= 3; ++$i) {
            self::assertSame(200, $this->post('/contact_message', $message), "message $i");
        }
        self::assertSame(429, $this->post('/contact_message', $message));
        self::assertGreaterThan(0, (int) $this->client->getResponse()->headers->get('Retry-After'));

        self::assertSame(200, $this->post('/contact_message', $message, '198.51.100.9'), 'other IP is not affected');
    }

    public function testPasswordResetRequestsAreLimited(): void
    {
        for ($i = 1; $i <= 3; ++$i) {
            self::assertNotSame(429, $this->post('/password/request_new', ['email' => 'nobody@example.test']));
        }
        self::assertSame(429, $this->post('/password/request_new', ['email' => 'nobody@example.test']));
    }

    public function testLoginIsThrottledAfterFiveFailures(): void
    {
        $user = UserFactory::createOne(['typeOfUser' => User::TYPE_USER, 'allowed' => true, 'verified' => true]);
        $credentials = ['username' => $user->getEmail(), 'password' => 'wrong-password'];

        for ($i = 1; $i <= 5; ++$i) {
            self::assertSame(401, $this->post('/login_check', $credentials), "attempt $i");
        }
        self::assertSame(429, $this->post('/login_check', $credentials));
        self::assertStringContainsString('Too many failed login attempts', (string) $this->client->getResponse()->getContent());
    }
}
