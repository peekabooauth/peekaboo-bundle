<?php

namespace Peekabooauth\PeekabooBundle\Controller;

use Peekabooauth\PeekabooBundle\Services\AuthRedirectBuilder;
use Peekabooauth\PeekabooBundle\Services\TargetBuilder;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
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

        return new RedirectResponse($this->authRedirectBuilder->getRedirectIdentityUrl());
    }
}
