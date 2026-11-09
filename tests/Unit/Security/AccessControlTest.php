<?php

namespace App\Tests\Unit\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Checks every route against the public allow-list in config/packages/security.yaml:
 * an anonymous request must be rejected with 401 unless the route is listed here.
 * A new route is therefore protected by default, and making one public is an explicit change.
 */
class AccessControlTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    /** "METHOD path" of every route that works without logging in. */
    private const PUBLIC_ROUTES = [
        'POST /login_check',
        'POST /user',
        'GET /verify_account',
        'POST /password/request_new',
        'POST /password/make_new',
        'GET /found_pet',
        'GET /vets/simplified',
        'GET /active_vets',
        'GET /doc',
        'GET /doc.json',
    ];

    public function testAnonymousRequestsAreRejectedExceptForPublicRoutes(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);
        /** @var RouterInterface $router */
        $router = static::getContainer()->get('router');

        $wrong = [];
        $checked = 0;
        foreach ($router->getRouteCollection() as $name => $route) {
            if (str_starts_with($name, '_')) {
                continue; // framework-internal routes (e.g. the dev-only error preview)
            }
            // Fill placeholders with a value that satisfies any requirement used in this project.
            $path = preg_replace('/\{[^}]+}/', '1', $route->getPath());
            foreach ($route->getMethods() ?: ['GET'] as $method) {
                $key = $method . ' ' . $route->getPath();
                $isPublic = in_array($key, self::PUBLIC_ROUTES, true);

                $client->request($method, $path);
                $status = $client->getResponse()->getStatusCode();
                $checked++;

                if ($isPublic && $status === 401) {
                    $wrong[] = "$key is public but answered 401";
                } elseif (!$isPublic && $status !== 401) {
                    $wrong[] = "$key answered $status without a token (expected 401)";
                }
            }
        }

        self::assertGreaterThan(50, $checked, 'Route collection looks incomplete.');
        self::assertSame([], $wrong);
    }
}
