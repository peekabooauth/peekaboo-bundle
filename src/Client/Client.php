<?php

namespace Peekabooauth\PeekabooBundle\Client;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\PeekabooBundle;
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
    public const X_API_KEY = 'x-api-key';

    /**
     * Peekaboo rejected the user's JWT (401) or did not find the API key (404): logged as a warning, not an error.
     * Note: peekaboo also answers a wrong JWT-path signature with 404, which this cannot tell apart.
     */
    private const REJECTED_CREDENTIALS_STATUSES = [Response::HTTP_UNAUTHORIZED, Response::HTTP_NOT_FOUND];

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
                'User-Agent' => self::userAgent(),
                self::X_CLIENT_IP => $clientIp,
                self::X_API_KEY => $apiKey
            ],
        ];

        // A key belongs to one app: the server must only look it up within this app.
        $urlApiKey = sprintf(
            '%s/api/user-by-key',
            rtrim($this->identityServerUrlInternal, '/')
        ) . '?' . http_build_query([
            'app' => $this->app,
            'signature' => $this->signature->generateSignature([$this->app], $this->secret),
        ]);

        return $this->getUser($options, $urlApiKey);
    }

    /** @throws Throwable */
    public function getUserByJwt(string $token, ?string $clientIp = null): UserDTO
    {
        $options = [
            'auth_bearer' => $token,
            'headers' => [
                'content-type' => 'application/json',
                'User-Agent' => self::userAgent(),
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

    /**
     * Exchanges the one-time code from the redirect for the JWT (server-to-server).
     *
     * @throws Throwable
     */
    public function exchangeCode(string $code, ?string $clientIp = null): string
    {
        $response = $this->httpClient->request(
            method: Request::METHOD_POST,
            url: sprintf('%s/api/token-by-code', rtrim($this->identityServerUrlInternal, '/')),
            options: [
                'headers' => [
                    'User-Agent' => self::userAgent(),
                    self::X_CLIENT_IP => $clientIp,
                ],
                'json' => [
                    'app' => $this->app,
                    'code' => $code,
                    'signature' => $this->signature->generateSignature([$this->app, $code], $this->secret),
                ],
            ]
        );
        $statusCode = $response->getStatusCode();
        if ($statusCode !== Response::HTTP_OK) {
            // The raw code is never logged.
            $this->logger->error('peekaboo_code_exchange_error', ['status_code' => $statusCode, 'content' => mb_substr($response->getContent(false), 0, 300)]);

            throw new RuntimeException('peekaboo_code_exchange_error');
        }

        $token = $response->toArray(false)['token'] ?? null;
        if (!is_string($token) || $token === '') {
            $this->logger->error('peekaboo_code_exchange_error', ['status_code' => $statusCode, 'reason' => 'missing_token']);

            throw new RuntimeException('peekaboo_code_exchange_error');
        }

        return $token;
    }

    private static function userAgent(): string
    {
        return 'peekaboo-bundle/' . PeekabooBundle::VERSION;
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
            $context = ['status_code' => $statusCode, 'content' => mb_substr($response->getContent(false), 0, 300)];
            if (in_array($statusCode, self::REJECTED_CREDENTIALS_STATUSES, true)) {
                // An expired/foreign JWT or an unknown API key: the caller's credentials, not a fault.
                $this->logger->warning('peekaboo_auth_error', $context);
            } else {
                // 5xx (peekaboo down), 403 (this app's signature/secret is wrong) and anything unexpected.
                $this->logger->error('peekaboo_auth_error', $context);
            }

            throw new RuntimeException('peekaboo_auth_error');
        }

        return new UserDTO($response->toArray());
    }
}
