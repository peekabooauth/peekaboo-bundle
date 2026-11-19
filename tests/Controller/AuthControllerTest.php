<?php

namespace Peekabooauth\PeekabooBundle\Tests\Controller;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\Client\DevHelper;
use Peekabooauth\PeekabooBundle\Controller\AuthController;
use Peekabooauth\PeekabooBundle\Services\AuthRedirectBuilder;
use Peekabooauth\PeekabooBundle\Services\TargetBuilder;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class AuthControllerTest extends TestCase
{
    private const string PEEKABOO_URL = 'https://peekabooauth.com/identity/auth?app=test';

    private function auth(Request $request, Client $client): Response
    {
        $request->setSession(new Session(new MockArraySessionStorage()));
        $stack = new RequestStack();
        $stack->push($request);

        $redirectBuilder = $this->createStub(AuthRedirectBuilder::class);
        $redirectBuilder->method('getRedirectIdentityUrl')->willReturn(self::PEEKABOO_URL);
        $targetBuilder = $this->createStub(TargetBuilder::class);
        $targetBuilder->method('getTargetUrl')->willReturn('/dashboard');

        $controller = new AuthController(
            new TokenStorage($stack, new DevHelper('https://peekabooauth.com'), $client, '__peekaboo_token'),
            $redirectBuilder,
            $targetBuilder,
        );

        return $controller->auth($request);
    }

    private function rejectingClient(): Client
    {
        $client = $this->createStub(Client::class);
        $client->method('exchangeCode')->willThrowException(new RuntimeException('code rejected'));

        return $client;
    }

    private static function cookie(Response $response, string $name): ?Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }

        return null;
    }

    public function testFirstVisitGoesToPeekabooWithoutCountingAFailure(): void
    {
        $response = $this->auth(Request::create('/peekaboo/auth'), $this->createStub(Client::class));

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(self::PEEKABOO_URL, $response->getTargetUrl());
        self::assertNull(self::cookie($response, AuthController::FAILURES_COOKIE));
    }

    public function testExchangedCodeGoesToTheTargetAndResetsTheCounter(): void
    {
        $client = $this->createStub(Client::class);
        $client->method('exchangeCode')->willReturn('jwt-1');
        $request = Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de'], [AuthController::FAILURES_COOKIE => '1']);

        $response = $this->auth($request, $client);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/dashboard', $response->getTargetUrl());
        self::assertTrue(self::cookie($response, AuthController::FAILURES_COOKIE)?->isCleared());
    }

    /** One failure may be a slow user or a blip: retry once through Peekaboo. */
    public function testFirstFailedExchangeRetriesThroughPeekaboo(): void
    {
        $request = Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de']);

        $response = $this->auth($request, $this->rejectingClient());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(self::PEEKABOO_URL, $response->getTargetUrl());
        $counter = self::cookie($response, AuthController::FAILURES_COOKIE);
        self::assertSame('1', $counter?->getValue());
        self::assertTrue($counter->isHttpOnly());
        self::assertGreaterThan(time(), $counter->getExpiresTime());
    }

    /** Peekaboo autologin would hand out a fresh code that fails the same way, until the browser gives up. */
    public function testSecondFailedExchangeStopsWithAnErrorPage(): void
    {
        $request = Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de'], [AuthController::FAILURES_COOKIE => '1']);

        $response = $this->auth($request, $this->rejectingClient());

        self::assertNotInstanceOf(RedirectResponse::class, $response);
        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        self::assertStringContainsString('Login failed', (string)$response->getContent());
        self::assertStringContainsString('href="/peekaboo/auth"', (string)$response->getContent());
        // "Try again" starts a fresh round and carries no stale token.
        self::assertTrue(self::cookie($response, AuthController::FAILURES_COOKIE)?->isCleared());
        self::assertTrue(self::cookie($response, '__peekaboo_token')?->isCleared());
    }

    public function testForgedCounterCannotSkipTheRetry(): void
    {
        $request = Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de'], [AuthController::FAILURES_COOKIE => '-5']);

        $response = $this->auth($request, $this->rejectingClient());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('1', self::cookie($response, AuthController::FAILURES_COOKIE)?->getValue());
    }

    /** The error page links to /peekaboo/auth under the app's base path, not the host root. */
    public function testRetryLinkKeepsTheBasePath(): void
    {
        $request = Request::create('/admin/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de'], [AuthController::FAILURES_COOKIE => '1'], [], [
            'SCRIPT_FILENAME' => '/app/public/index.php',
            'SCRIPT_NAME' => '/admin/index.php',
            'PHP_SELF' => '/admin/index.php',
        ]);

        $response = $this->auth($request, $this->rejectingClient());

        self::assertStringContainsString('href="/admin/peekaboo/auth"', (string)$response->getContent());
    }
}
