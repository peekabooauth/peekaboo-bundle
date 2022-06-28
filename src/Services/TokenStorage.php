<?php

namespace Peekabooauth\PeekabooBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TokenStorage
{
    private SessionInterface $session;

    public function __construct(private RequestStack $requestStack, private string $tokenName)
    {
        $this->session = $this->requestStack->getSession();
    }

    public function storageToken(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->get($this->tokenName)) {
            $this->session->set($this->tokenName, $request->get($this->tokenName));

            return true;
        }

        return false;
    }

    public function getToken(): ?string
    {
        return $this->session->get($this->tokenName);
    }

    public function clearToken(): ?string
    {
        return $this->session->remove($this->tokenName);
    }
}
