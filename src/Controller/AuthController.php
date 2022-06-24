<?php

namespace Gupalo\PeekabooBundle\Controller;

use Gupalo\PeekabooBundle\Services\AuthRedirectBuilder;
use Gupalo\PeekabooBundle\Services\TargetBuilder;
use Gupalo\PeekabooBundle\Services\TokenStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    public function __construct(
        private TokenStorage $tokenStorage,
        private AuthRedirectBuilder $authRedirectBuilder,
        private TargetBuilder $targetBuilder
    ) {
    }

    #[Route(path: '/peekaboo/auth', name: 'peekaboo_auth')]
    public function auth(): Response
    {
        if ($this->tokenStorage->storageToken()) {
            return new RedirectResponse($this->targetBuilder->getTargetUrl());
        }

        return new RedirectResponse($this->authRedirectBuilder->getRerirectIdentityUrl());
    }
}
