<?php

namespace Peekabooauth\PeekabooBundle\Tests\UserLoader;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\Client\DevHelper;
use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use Peekabooauth\PeekabooBundle\Tests\Services\JwtExpiryTest;
use Peekabooauth\PeekabooBundle\UserLoader\JwtTokenUserLoader;
use Peekabooauth\PeekabooBundle\UserLoader\TokenStorageUserLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/** A user cached by an older bundle (full 600s TTL, same key) must not outlive the token. */
class ExpiredTokenCacheTest extends TestCase
{
    private function warmCache(ArrayAdapter $cache, string $key): void
    {
        $cache->get($key, function () {
            return new UserDTO(['email' => 'vl@example.com', 'name' => 'vl', 'roles' => ['ROLE_USER']]);
        });
    }

    public function testBearerLoaderIgnoresCachedUserOfExpiredToken(): void
    {
        $jwt = JwtExpiryTest::jwt(['exp' => time() - 5]);
        $cache = new ArrayAdapter();
        $this->warmCache($cache, md5($jwt . JwtTokenUserLoader::class));
        $stack = new RequestStack();
        $stack->push(Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => $jwt]));

        $loader = new JwtTokenUserLoader($this->createStub(Client::class), $cache, $stack);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('peekaboo_jwt_expired');
        $loader->loadUser();
    }

    public function testSessionLoaderIgnoresCachedUserOfExpiredTokenAndDropsIt(): void
    {
        $jwt = JwtExpiryTest::jwt(['exp' => time() - 5]);
        $cache = new ArrayAdapter();
        $this->warmCache($cache, md5($jwt . TokenStorageUserLoader::class));

        $request = Request::create('/');
        $session = new Session(new MockArraySessionStorage());
        $session->set('__peekaboo_token', $jwt);
        $request->setSession($session);
        $stack = new RequestStack();
        $stack->push($request);
        $client = $this->createStub(Client::class);
        $storage = new TokenStorage($stack, new DevHelper('https://peekabooauth.com'), $client, '__peekaboo_token');

        $loader = new TokenStorageUserLoader($client, $cache, $storage, $stack);

        try {
            $loader->loadUser();
            self::fail('expected peekaboo_jwt_expired');
        } catch (RuntimeException $e) {
            self::assertSame('peekaboo_jwt_expired', $e->getMessage());
        }
        self::assertNull($session->get('__peekaboo_token'));
    }
}
