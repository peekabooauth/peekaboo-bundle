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

class UserProvider implements UserProviderInterface
{
    private Request $request;

    public function __construct(
        private Client $client,
        private TokenStorage $tokenStorage,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof UserDTO) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
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
        if ($this->isApiAuth()) {
            $token = $this->getToken();
        } else {
            $token = $this->tokenStorage->getToken();
        }

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

    private function getToken(): string
    {
        $result = $this->request->headers->get('Authorization', '');
        if ($result === '') {
            $result = $this->request->query->get('bearer', '');
        }
        if ($result === '') {
            $result = $this->request->request->get('bearer', '');
        }

        return $result;
    }

    private function isApiAuth(): bool
    {
        return
            $this->request->headers->get('Authorization', '') !== '' ||
            $this->request->query->get('bearer', '') !== '' ||
            $this->request->request->get('bearer', '') !== '';
    }
}
