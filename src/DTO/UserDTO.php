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

    /**
     * Exactly the roles Peekaboo granted for this application. ROLE_USER is not added: an app that gates
     * pages on it grants it through its role_hierarchy (before 1.8.9 / 1.12.0 every user, API keys included, got it).
     */
    public function getRoles(): array
    {
        return array_values(array_unique($this->roles));
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
