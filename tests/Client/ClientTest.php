<?php

namespace Peekabooauth\PeekabooBundle\Tests\Client;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\Client\DevHelper;
use Peekabooauth\PeekabooBundle\Services\Signature;
use PHPUnit\Framework\TestCase;
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
            httpClient: $this->createMock(HttpClientInterface::class),
            signature: $this->createMock(Signature::class),
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
}
