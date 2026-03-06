<?php

namespace Peekabooauth\PeekabooBundle\Client;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;

class DevHelper
{
    private const DEV_URL = 'https://peekabooauth.dev';
    private const DEFAULT_DEV_ROLES = ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_API', 'ROLE_DEV'];

    public function __construct(
        private readonly string $identityServerUrlInternal,
        private readonly string $devRoles = '',
    ) {
    }

    public function getUser(): ?UserDTO
    {
        if (!$this->isDev()) {
            return null;
        }

        $roles = $this->devRoles !== '' ? array_map('trim', explode(',', $this->devRoles)) : self::DEFAULT_DEV_ROLES;

        return new UserDTO([
            'email' => 'admin@localhost.net',
            'roles' => $roles,
            'name' => 'dev',
        ]);
    }

    public function isDev(): bool
    {
        return str_starts_with($this->identityServerUrlInternal, self::DEV_URL);
    }
}
