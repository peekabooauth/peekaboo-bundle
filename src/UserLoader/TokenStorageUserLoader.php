<?php

namespace Peekabooauth\PeekabooBundle\UserLoader;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\Client\DevHelper;
use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

class TokenStorageUserLoader implements TokenStorageUserLoaderInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly CacheInterface $cache,
        private readonly TokenStorage $tokenStorage,
    ) {
    }

    /**
     * @return UserDTO
     * @throws Throwable
     */
    public function loadUser(): UserInterface
    {
        try {
            $token = (string)$this->tokenStorage->getToken();
            return $this->cache->get(md5($token . __CLASS__), function (ItemInterface $item) use ($token) {
                $item->expiresAfter(600);

                return $this->client->getUserByJwt($token);
            });
        } catch (Throwable $e) {
            $this->tokenStorage->clearToken();

            throw $e;
        }
    }

    private function getToken(): ?string
    {
        return $this->tokenStorage->getToken();
    }

    public function isAuth(): bool
    {
        return ($this->getToken() ?? '') !== '';
    }

    public function clearToken(): void
    {
        $this->tokenStorage->clearToken();
    }
}
