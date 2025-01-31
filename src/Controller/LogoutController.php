<?php

namespace Peekabooauth\PeekabooBundle\Controller;

use Peekabooauth\PeekabooBundle\Services\AuthRedirectBuilder;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogoutController extends AbstractController
{
    public function __construct(
        private readonly TokenStorage $tokenStorage,
        private readonly AuthRedirectBuilder $authRedirectBuilder,
    ) {
    }

    #[Route(path: '/peekaboo/logout', name: 'peekaboo_logout')]
    public function auth(): Response
    {
        $response = new RedirectResponse($this->authRedirectBuilder->getRedirectIdentityLogoutUrl());
        $this->tokenStorage->clearToken($response);

        return $response;
    }
}
