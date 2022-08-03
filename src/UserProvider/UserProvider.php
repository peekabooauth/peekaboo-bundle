<?php

namespace Peekabooauth\PeekabooBundle\UserProvider;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;

class UserProvider implements UserProviderInterface
{
    public function __construct(
        private Client $client,
        private TokenStorage $tokenStorage
    ) {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof UserDTO) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
        }
        
        if ($this->tokenStorage->getToken() === null) {
            throw new UserNotFoundException('User not found.');  
        }

        return $this->getUser();
    }

    public function supportsClass(string $class): bool
    {
        return $class === UserDTO::class;
    }

    public function loadUserByIdentifier(?string $identifier = null): UserInterface
    {
        return $this->getUser();
    }

    private function getUser(): UserInterface
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            throw new UserNotFoundException('User not found.');
        }

        try {
            $user = $this->client->getUser($token);
        } catch (Throwable) {
            $this->tokenStorage->clearToken();
            throw new UserNotFoundException('User not found.');
        }

        return $user;
    }
}