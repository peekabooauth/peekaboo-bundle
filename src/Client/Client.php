<?php

namespace Peekabooauth\PeekabooBundle\Client;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class Client
{
    private string $url;

    public function __construct(
        string $identityServerUrlInternal,
        string $app,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->url = sprintf('%s/api/user/%s', rtrim($identityServerUrlInternal, '/'), $app);
    }

    /** @throws Throwable */
    public function getUser(string $token): UserDTO
    {
        $response = $this->httpClient->request(
            method: Request::METHOD_POST,
            url: $this->url,
            options: [
                'auth_bearer' => $token,
                'headers' => [
                    'content-type' => 'application/json',
                ],
            ]
        );
        $statusCode = $response->getStatusCode();
        if ($statusCode !== Response::HTTP_OK) {
            $this->logger->error('peekaboo_auth_error', ['status_code' => $statusCode, 'content' => mb_substr($response->getContent(false), 0, 300)]);

            throw new \RuntimeException('peekaboo_auth_error');
        }

        return new UserDTO($response->toArray());
    }
}
