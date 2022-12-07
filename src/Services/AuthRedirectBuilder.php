<?php

namespace Peekabooauth\PeekabooBundle\Services;

use Peekabooauth\PeekabooBundle\DTO\IdentityRequestDTO;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AuthRedirectBuilder
{
    public function __construct(
        private readonly string $identityServerUrlExternal,
        private readonly string $identityServerAuthPath,
        private readonly string $identityServerLogoutPath,
        private readonly string $routeAfterRedirect,
        private readonly string $app,
        private readonly string $secret,
        private readonly RouterInterface $router,
        private readonly Signature $signature,
    ){
    }

    public function getRedirectIdentityUrl(): string
    {
        $data = [
            'redirect_url' => $this->router->generate(
                'peekaboo_auth',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'app' => $this->app,
        ];

        $data['signature'] = $this->signature->generateSignature($data, $this->secret);

        $identityRequestDTO = new IdentityRequestDTO($data);

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
