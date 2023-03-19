<?php

namespace Peekabooauth\PeekabooBundle\Services;

use Peekabooauth\PeekabooBundle\Client\DevHelper;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Throwable;

class TokenStorage
{
    private SessionInterface $session;

    private Request $request;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly DevHelper $devHelper,
        private readonly string $tokenName,
    ) {
        try {
            $this->request = $this->requestStack?->getMainRequest() ?? new Request();
        } catch (Throwable) {
            $this->request = new Request();
        }
        try {
            $this->session = $this->requestStack?->getSession() ?? new Session();
        } catch (Throwable) {
            $this->session = new Session();
        }
    }

    /** @noinspection PhpRedundantOptionalArgumentInspection */
    public function storageToken(Response $response): bool
    {
        if ($this->devHelper->isDev()) {
            return true;
        }

        $request = $this->requestStack?->getCurrentRequest();
        if ($request) {
            $token = $request->get($this->tokenName);
            if ($token) {
                $this->session->set($this->tokenName, $token);

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

        return $this->session->get($this->tokenName) ?: $this->request->cookies->get($this->tokenName);
    }

    public function clearToken(?Response $response = null): ?string
    {
        $response?->headers->clearCookie($this->tokenName);

        return $this->session->remove($this->tokenName);
    }
}
