<?php

namespace Peekabooauth\PeekabooBundle\Security;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\UserProvider\ApiUserProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class ApiPeekabooAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ApiUserProvider $userProvider,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $this->getToken($request);
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->getToken($request);
        /** @var UserDTO $user */
        $user = $this->userProvider->loadUserByIdentifier($token);

        return new SelfValidatingPassport(new UserBadge($token));
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
        return new Response('Bad auth', 403);
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

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new Response('Need auth', 401);
    }
}
