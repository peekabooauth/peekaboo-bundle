<?php

namespace Gupalo\PeekabooBundle\Client;

use Gupalo\PeekabooBundle\DTO\UserDTO;
use GuzzleHttp\Client as BaseClient;
use GuzzleHttp\Psr7\Request;

class Client
{
    private const GET_USER_PATH = '/api/user';

    private BaseClient $client;

    public function __construct(string $baseIdentityServerUrl)
    {
        // @todo
        $options['base_uri'] = 'http://app_pb';
        //$options['base_uri'] = $baseIdentityServerUrl;
        $options['headers']['Content-Type'] = 'application/json';

        $this->client = new BaseClient($options);
    }

    public function getUser(string $token): UserDTO
    {
        $request = new Request('POST', self::GET_USER_PATH . '?bearer=' . $token);
        $response = $this->client->send($request);

        return new UserDTO(json_decode((string)$response->getBody(), true));
    }
}
