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

class JwtTokenUserLoader implements UserLoaderInterface
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
        return $this->cache->get(md5($this->getJwtToken() . __CLASS__), function (ItemInterface $item) {
            $item->expiresAfter(300);

            return $this->client->getUserByJwt($this->getJwtToken());
        });
    }

    public function isAuth(): bool
    {
        return
            (
                $this->getRequest()->headers->get('Authorization', '') !== '' &&
                !str_starts_with($this->getRequest()->headers->get('Authorization', ''), 'Basic ')
            ) ||
            $this->getRequest()->query->get('bearer', '') !== '' ||
            $this->getRequest()->request->get('bearer', '') !== '';
    }

    private function getJwtToken(): string
    {
        $result = $this->getRequest()->headers->get('Authorization', '');
        if ($result === '') {
            $result = $this->getRequest()->query->get('bearer', '');
        }
        if ($result === '') {
            $result = $this->getRequest()->request->get('bearer', '');
        }

        return $result;
    }

    private function getRequest(): Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
