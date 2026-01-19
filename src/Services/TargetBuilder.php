<?php

namespace Peekabooauth\PeekabooBundle\Services;

use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\RequestStack;

class TargetBuilder
{
    private const DEFAULT_FIREWALL_NAME = 'peekaboo';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FirewallMap $firewallMap,
    ) {
    }

    public function getTargetUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $this->requestStack->getSession();

        $firewallConfig = $this->firewallMap->getFirewallConfig($request);
        $firewallName = $firewallConfig?->getName() ?? self::DEFAULT_FIREWALL_NAME;

        return $session->get('_security.'.$firewallName.'.target_path', '/');
    }
}
