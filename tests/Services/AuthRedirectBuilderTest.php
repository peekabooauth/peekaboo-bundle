<?php

namespace Peekabooauth\PeekabooBundle\Tests\Services;

use Peekabooauth\PeekabooBundle\Services\AuthRedirectBuilder;
use Peekabooauth\PeekabooBundle\Services\Signature;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class AuthRedirectBuilderTest extends TestCase
{
    public function testIdentityUrlCarriesSignedVersion(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('https://app.test/peekaboo/auth');

        $builder = new AuthRedirectBuilder(
            identityServerUrlExternal: 'https://peekabooauth.com',
            identityServerAuthPath: '/auth',
            identityServerLogoutPath: '/logout',
            identityServerAutologin: '',
            routeAfterRedirect: 'home',
            app: 'test-app',
            secret: 's',
            router: $router,
            signature: new Signature(),
        );

        $url = $builder->getRedirectIdentityUrl();

        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame(['redirect_url', 'app', 'signature', 'autologin', 'v'], array_keys($query));
        self::assertSame('1.12.1', $query['v']);
        self::assertSame(
            new Signature()->generateSignature(['https://app.test/peekaboo/auth', 'test-app', '', '1.12.1'], 's'),
            $query['signature'],
        );
    }
}
