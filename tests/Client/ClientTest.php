<?php

namespace Peekabooauth\PeekabooBundle\Tests\Client;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\Client\DevHelper;
use Peekabooauth\PeekabooBundle\Services\Signature;
use Peekabooauth\PeekabooBundle\Tests\Services\JwtExpiryTest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client(
            identityServerUrlInternal: 'https://peekabooauth.dev',
            app: 'test-app',
            secret: 'test-secret',
            httpClient: $this->createStub(HttpClientInterface::class),
            signature: $this->createStub(Signature::class),
            devHelper: new DevHelper('https://peekabooauth.dev'),
        );
    }

    public function testGetUserByApiKey(): void
    {
        $user = $this->client->getUserByApiKey('test-api-key');

        self::assertSame('admin@localhost.net', $user->getUserIdentifier());
        self::assertSame('dev', $user->name);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_API', 'ROLE_DEV'], $user->getRoles());
    }

    public function testGetUserByApiKeyTellsServerWhichAppIsAsking(): void
    {
        $requestedUrl = null;
        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$requestedUrl): MockResponse {
            $requestedUrl = $url;

            return new MockResponse('{"email":"vl+api@example.com","name":"vl+api","roles":["ROLE_API"]}');
        });

        $client = new Client(
            identityServerUrlInternal: 'https://peekabooauth.com',
            app: 'vostok-admin',
            secret: 'admin-secret',
            httpClient: $httpClient,
            signature: new Signature(),
            devHelper: new DevHelper('https://peekabooauth.com'),
        );

        $client->getUserByApiKey('admin-key');

        parse_str((string)parse_url((string)$requestedUrl, PHP_URL_QUERY), $query);
        self::assertSame('https://peekabooauth.com/api/user-by-key', strtok((string)$requestedUrl, '?'));
        self::assertSame('vostok-admin', $query['app'] ?? null);
        self::assertSame(new Signature()->generateSignature(['vostok-admin'], 'admin-secret'), $query['signature'] ?? null);
    }

    public function testRequestsSendBundleUserAgent(): void
    {
        $headers = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$headers): MockResponse {
            $headers = $options['headers'];

            return new MockResponse('{"email":"vl+api@example.com","name":"vl+api","roles":["ROLE_API"]}');
        });

        $client = new Client(
            identityServerUrlInternal: 'https://peekabooauth.com',
            app: 'vostok-admin',
            secret: 'admin-secret',
            httpClient: $httpClient,
            signature: new Signature(),
            devHelper: new DevHelper('https://peekabooauth.com'),
        );

        $client->getUserByApiKey('k');

        self::assertContains('User-Agent: peekaboo-bundle/1.11.1', $headers);
    }

    private function exchangeClient(MockHttpClient $httpClient): Client
    {
        return new Client(
            identityServerUrlInternal: 'https://peekabooauth.com',
            app: 'vostok-admin',
            secret: 'admin-secret',
            httpClient: $httpClient,
            signature: new Signature(),
            devHelper: new DevHelper('https://peekabooauth.com'),
        );
    }

    /** @return iterable<string, array{int, string}> */
    public static function provideRejectedStatuses(): iterable
    {
        yield '401 expired JWT' => [401, 'warning'];
        yield '404 unknown API key' => [404, 'warning'];
        yield '403 wrong app signature' => [403, 'error'];
        yield '500 peekaboo error' => [500, 'error'];
        yield '502 peekaboo down' => [502, 'error'];
    }

    #[DataProvider('provideRejectedStatuses')]
    public function testGetUserLogsRejectedCredentialsAsWarningAndFaultsAsError(int $status, string $level): void
    {
        $body = '<html><head><title>' . $status . '</title></head></html>';
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method($level)
            ->with('peekaboo_auth_error', ['status_code' => $status, 'content' => $body]);
        $logger->expects(self::never())->method($level === 'error' ? 'warning' : 'error');

        $client = new Client(
            identityServerUrlInternal: 'https://peekabooauth.com',
            app: 'vostok-admin',
            secret: 'admin-secret',
            httpClient: new MockHttpClient(new MockResponse($body, ['http_code' => $status])),
            signature: new Signature(),
            devHelper: new DevHelper('https://peekabooauth.com'),
            logger: $logger,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('peekaboo_auth_error');

        $client->getUserByApiKey('admin-key');
    }

    public function testExchangeCodePostsSignedCodeAndReturnsToken(): void
    {
        $captured = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured): MockResponse {
            $captured = ['method' => $method, 'url' => $url, 'body' => $options['body'] ?? null, 'headers' => $options['headers']];

            return new MockResponse('{"token":"jwt-1"}');
        });

        $token = $this->exchangeClient($httpClient)->exchangeCode('c0de');

        self::assertSame('jwt-1', $token);
        self::assertSame('POST', $captured['method']);
        self::assertSame('https://peekabooauth.com/api/token-by-code', $captured['url']);
        self::assertSame(
            ['app' => 'vostok-admin', 'code' => 'c0de', 'signature' => md5('vostok-admin,c0de' . 'admin-secret')],
            json_decode((string)$captured['body'], true)
        );
        self::assertContains('User-Agent: peekaboo-bundle/1.11.1', $captured['headers']);
    }

    public function testExchangeCodeForwardsClientIp(): void
    {
        $captured = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured): MockResponse {
            $captured = $options['headers'];

            return new MockResponse('{"token":"jwt-1"}');
        });

        $this->exchangeClient($httpClient)->exchangeCode('c0de', '203.0.113.7');

        self::assertContains('x-client-ip: 203.0.113.7', $captured);
    }

    public function testExchangeCodeThrowsOnRejectedCode(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('{"error":"invalid_code"}', ['http_code' => 400]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('peekaboo_code_exchange_error');

        $this->exchangeClient($httpClient)->exchangeCode('bad');
    }

    public function testExpiredJwtIsRejectedWithoutAskingPeekaboo(): void
    {
        $httpClient = new MockHttpClient(function (): MockResponse {
            self::fail('an expired JWT must not reach peekaboo');
        });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('info')->with('peekaboo_jwt_expired', ['expired_at' => 1700000000]);
        $logger->expects(self::never())->method('warning');
        $logger->expects(self::never())->method('error');

        $client = new Client(
            identityServerUrlInternal: 'https://peekabooauth.com',
            app: 'vostok-admin',
            secret: 'admin-secret',
            httpClient: $httpClient,
            signature: new Signature(),
            devHelper: new DevHelper('https://peekabooauth.com'),
            logger: $logger,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('peekaboo_jwt_expired');

        $client->getUserByJwt(JwtExpiryTest::jwt(['exp' => 1700000000, 'username' => 'vl@example.com']));
    }

    /** @return iterable<string, array{string}> */
    public static function provideJwtsForPeekaboo(): iterable
    {
        yield 'not expired yet' => [JwtExpiryTest::jwt(['exp' => time() + 3600])];
        yield 'no exp claim' => [JwtExpiryTest::jwt(['username' => 'vl@example.com'])];
        yield 'not a JWT' => ['opaque-token'];
    }

    #[DataProvider('provideJwtsForPeekaboo')]
    public function testJwtWithoutPassedExpiryIsLeftToPeekaboo(string $token): void
    {
        $calls = 0;
        $httpClient = new MockHttpClient(function () use (&$calls): MockResponse {
            $calls++;

            return new MockResponse('{"email":"vl@example.com","name":"vl","roles":["ROLE_USER"]}');
        });

        $user = $this->exchangeClient($httpClient)->getUserByJwt($token);

        self::assertSame(1, $calls);
        self::assertSame('vl@example.com', $user->getUserIdentifier());
    }

    public function testExchangeCodeThrowsOnMissingToken(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('{"token":""}'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('peekaboo_code_exchange_error');

        $this->exchangeClient($httpClient)->exchangeCode('c0de');
    }
}
