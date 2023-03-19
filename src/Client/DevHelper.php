<?php

namespace Peekabooauth\PeekabooBundle\Client;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;

class DevHelper
{
    private const DEV_URL = 'https://peekabooauth.dev';

    public function __construct(
        private readonly string $identityServerUrlInternal,
    ) {
    }

    public function getUser(): ?UserDTO
    {
        if (!$this->isDev()) {
            return null;
        }

        return new UserDTO([
            'email' => 'admin@localhost.net',
            'roles' => ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_API', 'ROLE_DEV'],
            'name' => 'dev',
        ]);
    }

    public function isDev(): bool
    {
        return str_starts_with($this->identityServerUrlInternal, self::DEV_URL);
    }
}
