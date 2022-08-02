<?php

namespace Peekabooauth\PeekabooBundle\UserProvider;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;

class ApiUserProvider implements UserProviderInterface
{
    private Request $request;

    public function __construct(
        private Client $client,
        private TokenStorage $tokenStorage,
        RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof UserDTO) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
        }

        $token = $this->request->headers->get('Authorization', false);
        if ($token === false) {
            throw new UserNotFoundException('User not found.');
        }

        return $this->getUser($token);
    }

    public function supportsClass(string $class): bool
    {
        return $class === UserDTO::class;
    }

    public function loadUserByIdentifier(?string $identifier = null): UserInterface
    {
        return $this->getUser($identifier);
    }

    private function getUser(?string $identifier = null): UserInterface
    {
        if (!$identifier) {
            throw new UserNotFoundException('User not found.');
        }

        try {
            $user = $this->client->getUser($identifier);
        } catch (Throwable) {
            $this->tokenStorage->clearToken();
            throw new UserNotFoundException('User not found.');
        }

        return $user;
    }
}
