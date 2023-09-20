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

class ApiKeyUserLoader implements UserLoaderInterface
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
        return $this->cache->get(md5($this->getApiKey().__CLASS__), function (ItemInterface $item) {
            $item->expiresAfter(300);

            return $this->client->getUserByApiKey($this->getApiKey(), $this->getRequest()->getClientIp());
        });
    }

    public function isAuth(): bool
    {
        return
            $this->getRequest()->headers->get('x-api-key', '') !== '' ||
            $this->getRequest()->query->get('apikey', '') !== '' ||
            $this->getRequest()->request->get('apikey', '') !== '';
    }

    private function getApiKey(): string
    {
        $request = $this->getRequest();

        $result = $request->headers->get('x-api-key', '');
        if ($result === '') {
            $result = $request->headers->get('X-Api-Key', '');
        }
        if ($result === '') {
            $result = $request->query->get('apikey', '');
        }
        if ($result === '') {
            $result = $request->request->get('apikey', '');
        }

        return $result;
    }

    private function getRequest(): Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
