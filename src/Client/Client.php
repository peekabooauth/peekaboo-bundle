<?php

namespace Peekabooauth\PeekabooBundle\Client;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\Services\Signature;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class Client
{
    public const X_CLIENT_IP = 'x-client-ip';

    public function __construct(
        private readonly string $identityServerUrlInternal,
        private readonly string $app,
        private readonly string $secret,
        private readonly HttpClientInterface $httpClient,
        private readonly Signature $signature,
        private readonly DevHelper $devHelper,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /** @throws Throwable */
    public function getUserByApiKey(string $apiKey, ?string $clientIp = null): UserDTO
    {
        $options = [
            'headers' => [
                'content-type' => 'application/json',
                self::X_CLIENT_IP => $clientIp,
            ],
        ];

        $urlApiKey = sprintf(
            '%s/api/user-by-key',
            rtrim($this->identityServerUrlInternal, '/')
        );

        return $this->getUser($options, $urlApiKey);
    }

    /** @throws Throwable */
    public function getUserByJwt(string $token, ?string $clientIp = null): UserDTO
    {
        $options = [
            'auth_bearer' => $token,
            'headers' => [
                'content-type' => 'application/json',
                self::X_CLIENT_IP => $clientIp,
            ],
        ];

        $urlJwt = sprintf(
            '%s/api/user/%s',
            rtrim($this->identityServerUrlInternal, '/'),
            $this->app
        ) . '?signature=' . $this->signature->generateSignature([$this->app], $this->secret);

        return $this->getUser($options, $urlJwt);
    }

    /** @throws Throwable */
    private function getUser(array $options, string $url): UserDTO
    {
        $result = $this->devHelper->getUser();
        if ($result) {
            return $result;
        }

        $response = $this->httpClient->request(
            method: Request::METHOD_POST,
            url: $url,
            options: $options
        );
        $statusCode = $response->getStatusCode();
        if ($statusCode !== Response::HTTP_OK) {
            $this->logger->error('peekaboo_auth_error', ['status_code' => $statusCode, 'content' => mb_substr($response->getContent(false), 0, 300)]);

            throw new RuntimeException('peekaboo_auth_error');
        }

        return new UserDTO($response->toArray());
    }
}
