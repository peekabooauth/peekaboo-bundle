<?php

namespace Peekabooauth\PeekabooBundle\Controller;

use Peekabooauth\PeekabooBundle\Services\AuthRedirectBuilder;
use Peekabooauth\PeekabooBundle\Services\TargetBuilder;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    /** Consecutive failed code exchanges; a cookie, so it survives apps whose session does not persist. */
    public const string FAILURES_COOKIE = '__peekaboo_auth_failures';
    /** The first failure retries through Peekaboo (an expired code, a blip); the next one stops. */
    private const int MAX_FAILED_EXCHANGES = 2;
    private const int FAILURES_TTL = 300;

    public function __construct(
        private readonly TokenStorage $tokenStorage,
        private readonly AuthRedirectBuilder $authRedirectBuilder,
        private readonly TargetBuilder $targetBuilder,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    #[Route(path: '/peekaboo/auth', name: 'peekaboo_auth')]
    public function auth(Request $request): Response
    {
        $response = new RedirectResponse($this->targetBuilder->getTargetUrl());
        if ($this->tokenStorage->storageToken($response)) {
            $response->headers->clearCookie(self::FAILURES_COOKIE);

            return $response;
        }

        if ($this->tokenStorage->hasLoginCode()) {
            // Peekaboo's autologin answers every retry with a fresh code; if the exchange keeps failing
            // (Peekaboo unreachable, wrong app secret) redirecting again would loop until the browser gives up.
            $failures = max(0, (int)$request->cookies->get(self::FAILURES_COOKIE)) + 1;
            if ($failures >= self::MAX_FAILED_EXCHANGES) {
                $this->logger->error('peekaboo_login_failed', ['failed_exchanges' => $failures]);

                return $this->loginFailed($request);
            }

            $response->headers->setCookie(Cookie::create(
                name: self::FAILURES_COOKIE,
                value: (string)$failures,
                expire: time() + self::FAILURES_TTL,
                httpOnly: true,
            ));
        }

        $this->tokenStorage->clearToken($response);
        $response->setTargetUrl($this->authRedirectBuilder->getRedirectIdentityUrl());

        return $response;
    }

    private function loginFailed(Request $request): Response
    {
        $retryUrl = htmlspecialchars($request->getBaseUrl() . $request->getPathInfo(), ENT_QUOTES);

        $response = new Response(
            '<!doctype html><html><head><meta charset="utf-8"><title>Login failed</title></head><body>'
            . '<p>Login failed: Peekaboo could not confirm your login.</p>'
            . '<p><a href="' . $retryUrl . '">Try again</a></p>'
            . '</body></html>',
            Response::HTTP_SERVICE_UNAVAILABLE,
            ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-store'],
        );
        // "Try again" starts a fresh round: two attempts, and no stale token in the way.
        $response->headers->clearCookie(self::FAILURES_COOKIE);
        $this->tokenStorage->clearToken($response);

        return $response;
    }
}
