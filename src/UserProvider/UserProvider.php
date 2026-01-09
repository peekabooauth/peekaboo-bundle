<?php

namespace Peekabooauth\PeekabooBundle\UserProvider;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\UserLoader\UserLoaderRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserLoaderRegistry $userLoaderRegistry,
    ) {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof UserDTO) {
            throw new UnsupportedUserException(sprintf(
                'Instances of "%s" are not supported.',
                get_debug_type($user)
            ));
        }

        return $this->userLoaderRegistry->getUser();
    }

    public function supportsClass(string $class): bool
    {
        return $class === UserDTO::class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->userLoaderRegistry->getUser();
    }
}
