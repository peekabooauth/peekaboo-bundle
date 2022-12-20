<?php

namespace Peekabooauth\PeekabooBundle\UserLoader;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

class BasicAuthUserLoader implements UserLoaderInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly CacheInterface $cache,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return UserDTO
     * @throws Throwable
     */
    public function loadUser(): UserInterface
    {
        return $this->cache->get(md5($this->getApiKey() . __CLASS__), function (ItemInterface $item) {
            $item->expiresAfter(300);

            return $this->client->getUserByApiKey($this->getApiKey());
        });
    }

    public function isAuth(): bool
    {
        return $this->getRequest()->server->get('PHP_AUTH_USER', '') !== '';
    }

    private function getApiKey(): string
    {
        $user = $this->getRequest()->server->get('PHP_AUTH_USER');
        $pass = $this->getRequest()->server->get('PHP_AUTH_PW', '');
        $apiKey = $user;
        if ($pass !== '') {
            $apiKey .= ':' . $pass;
        }

        return $apiKey;
    }

    private function getRequest(): Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
