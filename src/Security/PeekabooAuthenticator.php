<?php

namespace Peekabooauth\PeekabooBundle\Security;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use Peekabooauth\PeekabooBundle\UserLoader\UserLoaderRegistry;
use Peekabooauth\PeekabooBundle\UserProvider\UserProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        private readonly UserLoaderRegistry $userLoaderRegistry,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('peekaboo_auth');
    }

    public function supports(Request $request): bool
    {
        return $this->userLoaderRegistry->isAuth();
    }

    public function authenticate(Request $request): Passport
    {
        /** @var UserDTO $user */
        $user = $this->userProvider->loadUserByIdentifier();

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
        if ($this->userLoaderRegistry->isApiAuth()) {
            $this->logger->warning('peekaboo_bad_auth_api', ['message' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);

            return $this->createForbiddenResponse($request);
        }

        $this->logger->warning('peekaboo_bad_auth', ['message' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
        $this->userLoaderRegistry->clearTokenStorageUser();

        return new RedirectResponse($this->getLoginUrl($request));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if ($this->userLoaderRegistry->isApiAuth()) {
            return $this->createUnauthorizedResponse($request);
        }

        return new RedirectResponse($this->getLoginUrl($request));
    }

    private function createUnauthorizedResponse(Request $request): Response|JsonResponse
    {
        return self::isJsonRequest($request) ?
            new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED) :
            new Response('unauthorized', Response::HTTP_UNAUTHORIZED);
    }

    private function createForbiddenResponse(Request $request): Response|JsonResponse
    {
        return self::isJsonRequest($request) ?
            new JsonResponse(['error' => 'forbidden'], Response::HTTP_FORBIDDEN) :
            new Response('forbidden', Response::HTTP_FORBIDDEN);
    }

    private static function isJsonRequest(Request $request): bool
    {
        return (
            $request->isXmlHttpRequest() ||
            in_array('application/json', $request->getAcceptableContentTypes()) ||
            in_array('application/x-json', $request->getAcceptableContentTypes()) ||
            in_array('application/javascript', $request->getAcceptableContentTypes()) ||
            in_array('application/x-javascript', $request->getAcceptableContentTypes()) ||
            in_array('text/javascript', $request->getAcceptableContentTypes())
        );
    }
}
