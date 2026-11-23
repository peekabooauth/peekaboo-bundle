<?php

namespace Peekabooauth\PeekabooBundle\UserLoader;

use Peekabooauth\PeekabooBundle\Client\Client;
use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\Services\JwtExpiry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use RuntimeException;
use Throwable;

class JwtTokenUserLoader implements UserLoaderInterface
{
    private const int USER_CACHE_TTL = 600;

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
        // Checked before the cache: an entry written by an older bundle still has the full 600s TTL.
        if (JwtExpiry::isExpired($this->getJwtToken())) {
            throw new RuntimeException('peekaboo_jwt_expired');
        }

        return $this->cache->get(md5($this->getJwtToken() . __CLASS__), function (ItemInterface $item) {
            // Never serve a cached user past the token's own expiry.
            $item->expiresAfter(JwtExpiry::cacheTtl($this->getJwtToken(), self::USER_CACHE_TTL));

            return $this->client->getUserByJwt($this->getJwtToken(), $this->getRequest()->getClientIp());
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
        // "Authorization: Bearer <jwt>" (OAuth clients: Claude, ChatGPT) carries the scheme; a bare token is
        // accepted too. Only the token may reach the client: HttpClient's auth_bearer refuses the space, so the
        // request to Peekaboo would never be sent.
        $result = trim((string)preg_replace('#^Bearer\s+#i', '', trim($this->getRequest()->headers->get('Authorization', ''))));
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
