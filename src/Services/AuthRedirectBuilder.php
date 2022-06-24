<?php

namespace Gupalo\PeekabooBundle\Services;

use Gupalo\PeekabooBundle\DTO\IdentityRequestDTO;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AuthRedirectBuilder
{
    public function __construct(
        private string $identityServerUrlExternal,
        private string $identityServerAuthPath,
        private RouterInterface $router
    ){
    }

    public function getRerirectIdentityUrl(): string
    {
        $identityRequestDTO = new IdentityRequestDTO([
            'redirect_url' => $this->router->generate('peekaboo_auth', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'app' => '' // @todo ?
        ]);

        return $this->identityServerUrlExternal .
            $this->identityServerAuthPath .
            '?' .
            http_build_query($identityRequestDTO->jsonSerialize());
    }
}
