<?php

namespace Peekabooauth\PeekabooBundle\Services;

use Peekabooauth\PeekabooBundle\Client\DevHelper;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Throwable;

class TokenStorage
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly DevHelper $devHelper,
        private readonly string $tokenName,
    ) {
    }

    public function storageToken(Response $response): bool
    {
        if ($this->devHelper->isDev()) {
            return true;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $token = $request->query->get($this->tokenName);
            if ($token) {
                $this->getSession()->set($this->tokenName, $token);

                $response->headers->setCookie(Cookie::create(
                    name: $this->tokenName,
                    value: $token,
                    expire: time() + 86400,
                    httpOnly: true,
                ));

                return true;
            }
        }

        return false;
    }

    public function getToken(): ?string
    {
        if ($this->devHelper->isDev()) {
            return 'dev';
        }

        $token = $this->getSession()->get($this->tokenName);
        if ($token) {
            return $token;
        }

        return $this->requestStack->getMainRequest()?->cookies->get($this->tokenName);
    }

    public function clearToken(?Response $response = null): ?string
    {
        $response?->headers->clearCookie($this->tokenName);

        return $this->getSession()->remove($this->tokenName);
    }

    private function getSession(): SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (Throwable) {
            return new Session();
        }
    }
}
