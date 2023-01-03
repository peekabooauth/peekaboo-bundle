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
            $item->expiresAfter(600);

            return $this->client->getUserByApiKey($this->getApiKey());
        });
    }

    public function isAuth(): bool
    {
        $this->forceBrowserAuthIfNeeded(); // otherwise only cli requests will work but not from browser ui

        return $this->getAuthUsername() !== '';
    }

    private function getApiKey(): string
    {
        $apiKey = $this->getAuthUsername();
        if ($this->getAuthPassword() !== '') {
            $apiKey .= ':' . $this->getAuthPassword();
        }

        return $apiKey;
    }

    private function getRequest(): Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private function getAuthUsername(): string
    {
        return (string)$this->getRequest()->server->get('PHP_AUTH_USER', '');
    }

    private function getAuthPassword(): string
    {
        return (string)$this->getRequest()->server->get('PHP_AUTH_PW', '');
    }

    private function forceBrowserAuthIfNeeded(): void
    {
        if (
            $this->getAuthUsername() === '' &&
            $this->isForceBasicAuth()
        ) {
            header('WWW-Authenticate: Basic realm="Auth"');
            header('HTTP/1.0 401 Unauthorized');
            echo('access denied');
            exit;
        }
    }

    private function isForceBasicAuth(): bool
    {
        return $this->getRequest()->query->get('auth', '') === 'basic';
    }
}
