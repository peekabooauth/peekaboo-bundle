<?php

namespace Peekabooauth\PeekabooBundle\UserLoader;

interface TokenStorageUserLoaderInterface extends UserLoaderInterface
{
    public function clearToken(): void;
}
