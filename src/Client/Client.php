<?php

namespace Peekabooauth\PeekabooBundle\Client;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use GuzzleHttp\Client as BaseClient;
use GuzzleHttp\Psr7\Request;

class Client
{
    private const GET_USER_PATH = '/api/user/%s';

    private BaseClient $client;

    public function __construct(
        string $identityServerUrlInternal,
        private string $app
    ) {
        $options['base_uri'] = $identityServerUrlInternal;
        $options['headers']['Content-Type'] = 'application/json';

        $this->client = new BaseClient($options);
    }

    public function getUser(string $token): UserDTO
    {
        $request = new Request(
            'POST',
            sprintf(self::GET_USER_PATH, $this->app) . '?bearer=' . $token
        );
        $response = $this->client->send($request);

        return new UserDTO(json_decode((string)$response->getBody(), true));
    }
}
