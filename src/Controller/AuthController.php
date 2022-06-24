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

            // @todo
            return new RedirectResponse('/peekaboo', 301);
        }

        // @todo referrer name in env
        $this->session->set('__peekaboo_referer', $request->headers->get('referer'));
        $identityRequestDTO = new IdentityRequestDTO([
            'redirect_url' => $request->getScheme() . '://atlas.loc:8001/peekaboo/auth', // @todo
            'app' => '' // @todo ?
        ]);

        // @todo env
        return new RedirectResponse($_ENV['IDENTITY_SERVER_URL'] . $_ENV['IDENTITY_SERVER_AUTH_PATH'] . '?' . http_build_query($identityRequestDTO->jsonSerialize()), 301);
    }
}
