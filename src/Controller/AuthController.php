<?php

namespace Gupalo\PeekabooBundle\Controller;

use Gupalo\PeekabooBundle\DTO\IdentityRequestDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    private SessionInterface $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    #[Route(path: '/peekaboo/auth', name: 'peekaboo_auth')]
    public function auth(Request $request): Response
    {
        // @todo token name in env
        if ($request->get('__peekaboo_token')) {
            $this->session->set('__peekaboo_token', $request->get('__peekaboo_token'));

            return new RedirectResponse($this->session->get('__peekaboo_referrer', '/'));
        }

        // @todo referrer name in env
        $this->session->set('__peekaboo_referrer', $request->headers->get('referer'));
        $identityRequestDTO = new IdentityRequestDTO([
            'redirect_url' => '?/peekaboo/auth', // @todo get full path with schema
            'app' => '' // @todo ?
        ]);

        // @todo
        return new RedirectResponse('identity_server_url?' . http_build_query($identityRequestDTO->jsonSerialize())); // @todo
    }
}
