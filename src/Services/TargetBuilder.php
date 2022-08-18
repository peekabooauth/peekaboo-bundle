<?php

namespace Peekabooauth\PeekabooBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\FirewallMapInterface;

class TargetBuilder
{
    private const DEFAULT_FIREWALL_NAME = 'peekaboo';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FirewallMapInterface $firewallMap,
    ) {
    }

    public function getTargetUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $session = $this->requestStack->getSession();

        $firewallName = null;
        try {
            if (method_exists($this->firewallMap, 'getFirewallConfig')) {
                $firewallConfig = $this->firewallMap->getFirewallConfig($request);
                $firewallName = $firewallConfig->getName();
            }
        } catch (\Throwable) {
        }
        if (!$firewallName) {
            $firewallName = self::DEFAULT_FIREWALL_NAME;
        }

        return $session->get('_security.'.$firewallName.'.target_path', '/');
    }
}
