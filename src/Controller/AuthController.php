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
        private readonly TokenStorage $tokenStorage,
        private readonly AuthRedirectBuilder $authRedirectBuilder,
        private readonly TargetBuilder $targetBuilder,
    ) {
    }

    #[Route(path: '/peekaboo/auth', name: 'peekaboo_auth')]
    public function auth(): Response
    {
        $response = new RedirectResponse($this->targetBuilder->getTargetUrl());
        $token = $this->tokenStorage->storageToken($response);
        if (!$token) {
            $response->setTargetUrl($this->authRedirectBuilder->getRedirectIdentityUrl());
        }

        return $response;
    }
}
