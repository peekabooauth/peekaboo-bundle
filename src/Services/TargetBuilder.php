<?php

namespace Gupalo\PeekabooBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

class TargetBuilder
{
    private SessionInterface $session;

    public function __construct(private RequestStack $requestStack, private FirewallMapInterface $firewallMap)
    {
        $this->session = $requestStack->getSession();
    }

    public function getTargerUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $firewallConfig = $this->firewallMap->getFirewallConfig($request);
        
        if ($firewallConfig === null) {
            return '/';
        }

        $firewallName = $firewallConfig->getName();

        $url = $this->session->get('_security.' . $firewallName . '.target_path', '/');
        
        return $url;
    }
}
