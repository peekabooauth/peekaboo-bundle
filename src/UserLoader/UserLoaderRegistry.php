<?php

namespace Peekabooauth\PeekabooBundle\UserLoader;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;
use Traversable;

class UserLoaderRegistry
{
    /** @var array|UserLoaderInterface[]  */
    private array $loaders;

    public function __construct(Traversable $loaders, array $priority)
    {
        $this->loaders = array_merge(array_flip($priority), iterator_to_array($loaders));
    }

    public function isApiAuth(): bool
    {
        foreach ($this->loaders as $loader) {
            if (!$loader instanceof TokenStorageUserLoaderInterface && $loader->isAuth()) {
                return true;
            }
        }

        return false;
    }

    public function isAuth(): bool
    {
        foreach ($this->loaders as $loader) {
            if ($loader->isAuth()) {
                return true;
            }
        }

        return false;
    }

    public function getUser(): UserInterface
    {
        if (!$this->isAuth()) {
            throw new UserNotFoundException('User not found.');
        }

        foreach ($this->loaders as $loader) {
            if ($loader->isAuth()) {
                try {
                    $user = $loader->loadUser();
                } catch (Throwable) {
                    throw new UserNotFoundException('User not found.');
                }
                if ($user instanceof UserInterface) {
                    return $user;
                }
            }
        }

        throw new UserNotFoundException('User not found.');
    }

    public function clearTokenStorageUser(): void
    {
        // get loader not api
        foreach ($this->loaders as $loader) {
            if ($loader instanceof TokenStorageUserLoaderInterface) {
                $loader->clearToken();
            }
        }
    }
}
