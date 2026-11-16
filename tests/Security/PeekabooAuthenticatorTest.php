<?php

namespace Peekabooauth\PeekabooBundle\Tests\Security;

use ArrayIterator;
use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\Client\DevHelper;
use Peekabooauth\PeekabooBundle\Security\PeekabooAuthenticator;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use Peekabooauth\PeekabooBundle\UserLoader\JwtTokenUserLoader;
use Peekabooauth\PeekabooBundle\UserLoader\TokenStorageUserLoader;
use Peekabooauth\PeekabooBundle\UserLoader\UserLoaderRegistry;
use Peekabooauth\PeekabooBundle\UserProvider\UserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class PeekabooAuthenticatorTest extends TestCase
{
    private Session $session;

    private function authenticator(Request $request, bool $withJwtLoader = false): PeekabooAuthenticator
    {
        $this->session = new Session(new MockArraySessionStorage());
        $request->setSession($this->session);
        $stack = new RequestStack();
        $stack->push($request);

        $client = $this->createStub(Client::class);
        $tokenStorage = new TokenStorage(
            requestStack: $stack,
            devHelper: new DevHelper('https://peekabooauth.com'),
            client: $client,
            tokenName: '__peekaboo_token',
        );
        $loaders = [TokenStorageUserLoader::class => new TokenStorageUserLoader($client, new ArrayAdapter(), $tokenStorage, $stack)];
        if ($withJwtLoader) {
            $loaders = [JwtTokenUserLoader::class => new JwtTokenUserLoader($client, new ArrayAdapter(), $stack)] + $loaders;
        }
        $registry = new UserLoaderRegistry(new ArrayIterator($loaders), []);
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/peekaboo/auth');

        return new PeekabooAuthenticator(
            router: $router,
            userProvider: $this->createStub(UserProvider::class),
            userLoaderRegistry: $registry,
            tokenStorage: $tokenStorage,
        );
    }

    public function testFailureExpiresTheTokenCookieSoTheNextRequestCannotLoop(): void
    {
        $request = Request::create('/dashboard', 'GET', [], ['__peekaboo_token' => 'rejected-jwt']);
        $authenticator = $this->authenticator($request);
        $this->session->set('__peekaboo_token', 'rejected-jwt');

        $response = $authenticator->onAuthenticationFailure($request, new UserNotFoundException());

        self::assertSame('/peekaboo/auth', $response->headers->get('Location'));
        self::assertNull($this->session->get('__peekaboo_token'));
        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);
        self::assertSame('__peekaboo_token', $cookies[0]->getName());
        self::assertTrue($cookies[0]->isCleared());
    }

    public function testStaleCookieDoesNotHijackTheAuthRoute(): void
    {
        // A fresh ?__peekaboo_code must reach AuthController even when an old, rejected cookie is still sent.
        $request = Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de'], ['__peekaboo_token' => 'rejected-jwt']);
        $request->attributes->set('_route', 'peekaboo_auth');

        self::assertFalse($this->authenticator($request)->supports($request));
    }

    public function testApiCredentialsAreStillCheckedOnTheAuthRoute(): void
    {
        // Bad API credentials must keep failing with 401/403 here, not fall through to the controller's redirect.
        $request = Request::create('/peekaboo/auth', 'GET', [], ['__peekaboo_token' => 'rejected-jwt'], [], ['HTTP_AUTHORIZATION' => 'Bearer bad-jwt']);
        $request->attributes->set('_route', 'peekaboo_auth');

        self::assertTrue($this->authenticator($request, withJwtLoader: true)->supports($request));
    }

    public function testOtherRoutesWithACookieAreStillAuthenticated(): void
    {
        $request = Request::create('/dashboard', 'GET', [], ['__peekaboo_token' => 'jwt']);
        $request->attributes->set('_route', 'dashboard');

        self::assertTrue($this->authenticator($request)->supports($request));
    }
}
