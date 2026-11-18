<?php

namespace Peekabooauth\PeekabooBundle\Tests\Services;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\Client\DevHelper;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class TokenStorageTest extends TestCase
{
    private Session $session;

    private function storage(Request $request, Client $client, ?LoggerInterface $logger = null): TokenStorage
    {
        $this->session = new Session(new MockArraySessionStorage());
        $request->setSession($this->session);
        $stack = new RequestStack();
        $stack->push($request);

        return new TokenStorage(
            requestStack: $stack,
            devHelper: new DevHelper('https://peekabooauth.com'),
            client: $client,
            tokenName: '__peekaboo_token',
            codeName: '__peekaboo_code',
            logger: $logger ?? new \Psr\Log\NullLogger(),
        );
    }

    public function testCodeIsExchangedAndStoredLikeAToken(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::once())->method('exchangeCode')->with('c0de')->willReturn('jwt-1');
        $response = new Response();

        $storage = $this->storage(Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de']), $client);

        self::assertTrue($storage->storageToken($response));
        self::assertSame('jwt-1', $this->session->get('__peekaboo_token'));
        $cookie = $response->headers->getCookies()[0];
        self::assertSame('__peekaboo_token', $cookie->getName());
        self::assertSame('jwt-1', $cookie->getValue());
        self::assertTrue($cookie->isHttpOnly());
    }

    public function testCodeExchangeForwardsUserIp(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::once())->method('exchangeCode')->with('c0de', '203.0.113.7')->willReturn('jwt-1');

        $request = Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de'], [], [], ['REMOTE_ADDR' => '203.0.113.7']);

        self::assertTrue($this->storage($request, $client)->storageToken(new Response()));
    }

    public function testLegacyTokenStillAccepted(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::never())->method('exchangeCode');
        $response = new Response();

        $storage = $this->storage(Request::create('/peekaboo/auth', 'GET', ['__peekaboo_token' => 'jwt-2']), $client);

        self::assertTrue($storage->storageToken($response));
        self::assertSame('jwt-2', $this->session->get('__peekaboo_token'));
        self::assertSame('jwt-2', $response->headers->getCookies()[0]->getValue());
    }

    public function testCookieExpiresWithTheJwt(): void
    {
        $exp = time() + 3600;
        $jwt = JwtExpiryTest::jwt(['exp' => $exp, 'username' => 'vl@example.com']);
        $client = $this->createStub(Client::class);
        $client->method('exchangeCode')->willReturn($jwt);
        $response = new Response();

        self::assertTrue($this->storage(Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de']), $client)->storageToken($response));

        self::assertSame($exp, $response->headers->getCookies()[0]->getExpiresTime());
    }

    public function testCookieWithoutReadableExpiryLastsADay(): void
    {
        $response = new Response();
        $before = time();

        $storage = $this->storage(Request::create('/peekaboo/auth', 'GET', ['__peekaboo_token' => 'opaque']), $this->createStub(Client::class));
        self::assertTrue($storage->storageToken($response));

        $expires = $response->headers->getCookies()[0]->getExpiresTime();
        self::assertGreaterThanOrEqual($before + 86400, $expires);
        self::assertLessThanOrEqual(time() + 86400, $expires);
    }

    public function testFailedExchangeStoresNothing(): void
    {
        $client = $this->createStub(Client::class);
        $client->method('exchangeCode')->willThrowException(new RuntimeException('peekaboo_code_exchange_error'));
        $response = new Response();

        $storage = $this->storage(Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'bad']), $client);

        self::assertFalse($storage->storageToken($response));
        self::assertNull($this->session->get('__peekaboo_token'));
        self::assertSame([], $response->headers->getCookies());
    }

    public function testFailedExchangeLogsOnlyExceptionClass(): void
    {
        $client = $this->createStub(Client::class);
        $client->method('exchangeCode')->willThrowException(new RuntimeException('secret-message'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')
            ->with('peekaboo_code_exchange_failed', ['exception' => RuntimeException::class]);

        $storage = $this->storage(Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de']), $client, $logger);

        self::assertFalse($storage->storageToken(new Response()));
    }

    public function testCodeWinsOverLegacyToken(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::once())->method('exchangeCode')->with('c0de')->willReturn('jwt-1');

        $storage = $this->storage(Request::create('/peekaboo/auth', 'GET', ['__peekaboo_code' => 'c0de', '__peekaboo_token' => 'legacy']), $client);

        self::assertTrue($storage->storageToken(new Response()));
        self::assertSame('jwt-1', $this->session->get('__peekaboo_token'));
    }
}
