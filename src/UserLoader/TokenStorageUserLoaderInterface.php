<?php

namespace Peekabooauth\PeekabooBundle\UserLoader;

use Symfony\Component\Security\Core\User\UserInterface;

interface TokenStorageUserLoaderInterface extends UserLoaderInterface
{
    public function clearToken(): void;
}
