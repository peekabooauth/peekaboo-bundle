<?php

namespace Peekabooauth\PeekabooBundle\Tests\UserLoader;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\Tests\Services\JwtExpiryTest;
use Peekabooauth\PeekabooBundle\UserLoader\JwtTokenUserLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * An OAuth client (Claude, ChatGPT, Claude Code) sends "Authorization: Bearer <jwt>". The scheme must not reach
 * HttpClient's auth_bearer option: it refuses the space and no request is ever sent, so every such call was a 401.
 */
class JwtTokenUserLoaderTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function dataHeader(): iterable
    {
        yield 'Bearer scheme' => ['Bearer %s'];
        yield 'lowercase scheme' => ['bearer %s'];
        yield 'extra spaces' => ['Bearer   %s '];
        yield 'bare token' => ['%s'];
    }

    #[DataProvider('dataHeader')]
    public function testTheAuthorizationHeaderGivesTheClientTheBareToken(string $header): void
    {
        $jwt = JwtExpiryTest::jwt(['exp' => time() + 600]);
        $user = new UserDTO(['email' => 'vl@example.com', 'name' => 'vl', 'roles' => ['ROLE_USER']]);
        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('getUserByJwt')->with($jwt, $this->anything())->willReturn($user);

        $loader = new JwtTokenUserLoader($client, new ArrayAdapter(), $this->stack(['HTTP_AUTHORIZATION' => sprintf($header, $jwt)]));

        self::assertTrue($loader->isAuth());
        self::assertSame($user, $loader->loadUser());
    }

    public function testTheQueryParameterStillWorks(): void
    {
        $jwt = JwtExpiryTest::jwt(['exp' => time() + 600]);
        $user = new UserDTO(['email' => 'vl@example.com', 'name' => 'vl', 'roles' => ['ROLE_USER']]);
        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('getUserByJwt')->with($jwt, $this->anything())->willReturn($user);
        $stack = new RequestStack();
        $stack->push(Request::create('/?bearer=' . $jwt));

        self::assertSame($user, (new JwtTokenUserLoader($client, new ArrayAdapter(), $stack))->loadUser());
    }

    public function testAnExpiredTokenBehindTheSchemeIsStillRejectedLocally(): void
    {
        $jwt = JwtExpiryTest::jwt(['exp' => time() - 5]);
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('getUserByJwt');

        $loader = new JwtTokenUserLoader($client, new ArrayAdapter(), $this->stack(['HTTP_AUTHORIZATION' => 'Bearer ' . $jwt]));

        $this->expectExceptionMessage('peekaboo_jwt_expired');
        $loader->loadUser();
    }

    /** @param array<string, string> $server */
    private function stack(array $server): RequestStack
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/', 'GET', [], [], [], $server));

        return $stack;
    }
}
