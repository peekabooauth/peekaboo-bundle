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
    private string $urlJwt;
    private string $urlApiKey;

    public function __construct(
        string $identityServerUrlInternal,
        string $app,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->urlJwt = sprintf('%s/api/user/%s', rtrim($identityServerUrlInternal, '/'), $app);
        $this->urlApiKey = sprintf('%s/api/user-by-key', rtrim($identityServerUrlInternal, '/'));
    }

    /** @throws Throwable */
    public function getUserByApiKey(string $apiKey): UserDTO
    {
        $options = [
            'headers' => [
                'content-type' => 'application/json',
                'x-api-key' => $apiKey
            ],
        ];

        return $this->getUser($options, $this->urlApiKey);
    }

    /** @throws Throwable */
    public function getUserByJwt(string $token): UserDTO
    {
        $options = [
            'auth_bearer' => $token,
            'headers' => [
                'content-type' => 'application/json',
            ],
        ];

        return $this->getUser($options, $this->urlJwt);
    }

    /** @throws Throwable */
    private function getUser(array $options, string $url): UserDTO
    {
        $response = $this->httpClient->request(
            method: Request::METHOD_POST,
            url: $url,
            options: $options
        );
        $statusCode = $response->getStatusCode();
        if ($statusCode !== Response::HTTP_OK) {
            $this->logger->error('peekaboo_auth_error', ['status_code' => $statusCode, 'content' => mb_substr($response->getContent(false), 0, 300)]);

            throw new \RuntimeException('peekaboo_auth_error');
        }

        return new UserDTO($response->toArray());
    }
}
