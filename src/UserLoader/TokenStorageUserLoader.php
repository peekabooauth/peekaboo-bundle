<?php

namespace Peekabooauth\PeekabooBundle\UserLoader;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\Services\JwtExpiry;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use RuntimeException;
use Throwable;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

class TokenStorageUserLoader implements TokenStorageUserLoaderInterface
{
    private const int USER_CACHE_TTL = 600;

    public function __construct(
        private readonly Client $client,
        private readonly CacheInterface $cache,
        private readonly TokenStorage $tokenStorage,
        private readonly RequestStack $requestStack,
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
            // Checked before the cache: an entry written by an older bundle still has the full 600s TTL.
            if (JwtExpiry::isExpired($token)) {
                throw new RuntimeException('peekaboo_jwt_expired');
            }

            return $this->cache->get(md5($token . __CLASS__), function (ItemInterface $item) use ($token) {
                // Never serve a cached user past the token's own expiry.
                $item->expiresAfter(JwtExpiry::cacheTtl($token, self::USER_CACHE_TTL));

                return $this->client->getUserByJwt($token, $this->getRequest()->getClientIp());
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

    public function getRequest(): Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
