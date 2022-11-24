<?php

namespace Peekabooauth\PeekabooBundle\Security;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\Services\TokenStorage;
use Peekabooauth\PeekabooBundle\UserProvider\UserProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class PeekabooAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly UserProvider $userProvider,
        private readonly TokenStorage $tokenStorage,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('peekaboo_auth');
    }

    public function supports(Request $request): bool
    {
        return (
            ($this->tokenStorage->getToken() ?? '') !== '' ||
            $this->getToken($request) !== ''
        );
    }

    public function authenticate(Request $request): Passport
    {
        if ($this->isApiAuth($request)) {
            $token = $this->getToken($request);
            $identifier = null;
        } else {
            $token = $this->tokenStorage->getToken();
            $identifier = $token;
        }

        /** @var UserDTO $user */
        $user = $this->userProvider->loadUserByIdentifier($token);
        if ($identifier === null) {
            $identifier = $user->email;
        }

        return new SelfValidatingPassport(new UserBadge($identifier));
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new UsernamePasswordToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($this->isApiAuth($request)) {
            $this->logger->warning('peekaboo_bad_auth', ['message' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);

            return new Response('Bad auth', 403);
        }

        $this->tokenStorage->clearToken();

        return new RedirectResponse($this->getLoginUrl($request));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        if ($this->isApiAuth($request)) {
            return new Response('Need auth', 401);
        }

        return new RedirectResponse($this->getLoginUrl($request));
    }

    private function getToken(Request $request): string
    {
        $result = $request->headers->get('Authorization', '');
        if ($result === '') {
            $result = $request->query->get('bearer', '');
        }
        if ($result === '') {
            $result = $request->request->get('bearer', '');
        }

        return $result;
    }

    private function isApiAuth(Request $request): bool
    {
        return
            $request->headers->get('Authorization', '') !== '' ||
            $request->query->get('bearer', '') !== '' ||
            $request->request->get('bearer', '') !== '';
    }
}
