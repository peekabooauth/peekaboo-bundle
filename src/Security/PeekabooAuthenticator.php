<?php

namespace Gupalo\PeekabooBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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
        private UserProvider $userProvider,
        private RequestStack $requestStack,
        private RouterInterface $router
    ) {
    }

    protected function getLoginUrl(Request $request): string
    {
        // @todo
        return '';
    }

    public function supports(Request $request): bool
    {
        if ($this->requestStack->getSession()->get('__peekaboo_token', null)) {
            return true;
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $user = $this->userProvider->loadUserByIdentifier($this->requestStack->getSession()->get('__peekaboo_token'));

        return new SelfValidatingPassport(new UserBadge($user->email));

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
        // @todo redirect clear peekaboo token
        return new Response('fail');
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $url = $this->router->generate('peekaboo_auth');

        return new RedirectResponse($url);
    }
}
