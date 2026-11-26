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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
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

    private function authenticator(Request $request, bool $withJwtLoader = false, ?Security $security = null): PeekabooAuthenticator
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
            security: $security,
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

    private function securityWithFirewall(bool $stateless): Security
    {
        $security = $this->createStub(Security::class);
        $security->method('getFirewallConfig')->willReturn(new FirewallConfig('main', 'security.user_checker', stateless: $stateless));

        return $security;
    }

    /**
     * @return iterable<string, array{bool, string, int}>
     */
    public static function dataEntryPoint(): iterable
    {
        // A stateless firewall cannot keep a login session, so redirecting an API client to the HTML login only
        // hides the real answer. A browser navigation (Accept: text/html) still gets the login redirect, because
        // the token cookie lets a browser use a stateless firewall too.
        yield 'stateless, json client' => [true, 'application/json', 401];
        yield 'stateless, json-ld client' => [true, 'application/ld+json', 401];
        yield 'stateless, curl default accept' => [true, '*/*', 401];
        yield 'stateless, browser navigation' => [true, 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 302];
        yield 'stateful, json client' => [false, 'application/json', 302];
        yield 'stateful, browser navigation' => [false, 'text/html', 302];
    }

    #[DataProvider('dataEntryPoint')]
    public function testEntryPointWithoutCredentials(bool $stateless, string $accept, int $expectedStatus): void
    {
        $request = Request::create('/api/things', 'GET', [], [], [], ['HTTP_ACCEPT' => $accept]);
        $authenticator = $this->authenticator($request, security: $this->securityWithFirewall($stateless));

        $response = $authenticator->start($request);

        self::assertSame($expectedStatus, $response->getStatusCode());
        if ($expectedStatus === 302) {
            self::assertSame('/peekaboo/auth', $response->headers->get('Location'));
        }
    }

    public function testUnauthorizedJsonLdClientGetsAJsonBody(): void
    {
        $request = Request::create('/api/things', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/ld+json']);
        $authenticator = $this->authenticator($request, security: $this->securityWithFirewall(true));

        $response = $authenticator->start($request);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('{"error":"unauthorized"}', $response->getContent());
    }

    public function testWithoutTheSecurityServiceTheEntryPointStillRedirects(): void
    {
        $request = Request::create('/api/things', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $this->authenticator($request)->start($request);

        self::assertSame(302, $response->getStatusCode());
    }
}
