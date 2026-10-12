<?php

namespace Peekabooauth\PeekabooBundle\DTO;

use JsonSerializable;
use Symfony\Component\Security\Core\User\UserInterface;

class UserDTO implements UserInterface, JsonSerializable
{
    public string $email;

    public string $name;

    public array $roles = [];

    public function __construct(array $data)
    {
        $this->email = $data['email'];
        $this->roles = $data['roles'];
        $this->name = $data['name'];
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function jsonSerialize(): array
    {
        return [
            'email' => $this->email,
            'roles' => $this->roles,
            'name' => $this->name,
        ];
    }
}
