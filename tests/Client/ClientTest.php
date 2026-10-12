<?php

namespace Peekabooauth\PeekabooBundle\Tests\Client;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\Client\DevHelper;
use Peekabooauth\PeekabooBundle\Services\Signature;
use PHPUnit\Framework\TestCase;
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
        self::assertSame((new Signature())->generateSignature(['vostok-admin'], 'admin-secret'), $query['signature'] ?? null);
    }
}
