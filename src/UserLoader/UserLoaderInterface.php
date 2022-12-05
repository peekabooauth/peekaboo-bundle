<?php

namespace Peekabooauth\PeekabooBundle\UserLoader;

use Symfony\Component\Security\Core\User\UserInterface;

interface UserLoaderInterface
{
    public function loadUser(): UserInterface;

    public function isAuth(): bool;
}
