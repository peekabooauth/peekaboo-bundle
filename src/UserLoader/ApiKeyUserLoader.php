<?php

namespace Peekabooauth\PeekabooBundle\UserLoader;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ApiKeyUserLoader implements UserLoaderInterface
{
    private Request $request;

    public function __construct(
        private readonly Client $client,
        private readonly CacheInterface $cache,
        RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @return UserDTO
     * @throws \Throwable
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
        return
            $this->request->headers->get('x-api-key', '') !== '' ||
            $this->request->query->get('x-api-key', '') !== '' ||
            $this->request->request->get('x-api-key', '') !== '';
    }

    private function getApiKey(): string
    {
        $result = $this->request->headers->get('x-api-key', '');
        if ($result === '') {
            $result = $this->request->query->get('x-api-key', '');
        }
        if ($result === '') {
            $result = $this->request->request->get('x-api-key', '');
        }

        return $result;
    }
}
