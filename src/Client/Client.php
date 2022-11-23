<?php

namespace Peekabooauth\PeekabooBundle\Client;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use GuzzleHttp\Client as BaseClient;
use GuzzleHttp\Psr7\Request;
use Throwable;

class Client
{
    private const GET_USER_PATH = '/api/user/%s';

    private BaseClient $client;

    public function __construct(
        string $identityServerUrlInternal,
        private readonly string $app,
    ) {
        $options['base_uri'] = $identityServerUrlInternal;
        $options['headers']['Content-Type'] = 'application/json';

        $this->client = new BaseClient($options);
    }

    /** @throws Throwable */
    public function getUser(string $token): UserDTO
    {
        $request = new Request(
            method: 'POST',
            uri: sprintf(self::GET_USER_PATH, $this->app) . '?bearer=' . $token
        );
        //$request = new Request(
        //    method: 'POST',
        //    uri: sprintf(self::GET_USER_PATH, $this->app),
        //    headers: [
        //        'Authorization: Bearer ' . $token,
        //    ]
        //);
        $response = $this->client->send($request);

        return new UserDTO(json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }
}
