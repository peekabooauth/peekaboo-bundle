<?php

namespace Peekabooauth\PeekabooBundle\UserLoader;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class JwtTokenUserLoader implements UserLoaderInterface
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
        return $this->cache->get(md5($this->getJwtToken() . __CLASS__), function (ItemInterface $item) {
            $item->expiresAfter(300);

            return $this->client->getUserByJwt($this->getJwtToken());
        });
    }

    public function isAuth(): bool
    {
        return
            $this->request->headers->get('Authorization', '') !== '' ||
            $this->request->query->get('bearer', '') !== '' ||
            $this->request->request->get('bearer', '') !== '';
    }

    private function getJwtToken(): string
    {
        $result = $this->request->headers->get('Authorization', '');
        if ($result === '') {
            $result = $this->request->query->get('bearer', '');
        }
        if ($result === '') {
            $result = $this->request->request->get('bearer', '');
        }

        return $result;
    }
}
