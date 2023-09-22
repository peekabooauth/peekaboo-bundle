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
        private readonly string $identityServerAutologin,
        private readonly string $routeAfterRedirect,
        private readonly string $app,
        private readonly string $secret,
        private readonly RouterInterface $router,
        private readonly Signature $signature,
    ){
    }

    public function getRedirectIdentityUrl(): string
    {
        $redirectUrl = $this->router->generate(
            name: 'peekaboo_auth',
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $data = [
            'redirect_url' => $redirectUrl,
            'app' => $this->app,
            'autologin' => $this->identityServerAutologin,
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
        $redirectUrl = $this->router->generate(
            name: $this->routeAfterRedirect,
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return implode('', [
            $this->identityServerUrlExternal,
            $this->identityServerLogoutPath,
            '?redirect_url=',
            $redirectUrl,
        ]);
    }
}
