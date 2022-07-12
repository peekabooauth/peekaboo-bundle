<?php

namespace Peekabooauth\PeekabooBundle\Services;

use Peekabooauth\PeekabooBundle\DTO\IdentityRequestDTO;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AuthRedirectBuilder
{
    public function __construct(
        private string $identityServerUrlExternal,
        private string $identityServerAuthPath,
        private string $identityServerLogoutPath,
        private string $routeAfterRedirect,
        private string $app,
        private string $secret,
        private RouterInterface $router
    ){
    }

    public function getRerirectIdentityUrl(): string
    {
        $identityRequestDTO = new IdentityRequestDTO([
            'redirect_url' => $this->router->generate('peekaboo_auth', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'app' => $this->app,
            'secret' => $this->secret
        ]);

        return $this->identityServerUrlExternal .
            $this->identityServerAuthPath .
            '?' .
            http_build_query($identityRequestDTO->jsonSerialize());
    }

    public function getRedirectIdentityLogoutUrl(): string
    {
        return $this->identityServerUrlExternal .
            $this->identityServerLogoutPath .
            '?redirect_url=' .
            $this->router->generate($this->routeAfterRedirect, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
