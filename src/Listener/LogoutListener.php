<?php

namespace Peekabooauth\PeekabooBundle\Listener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $logoutEvent): void
    {
        $request = $logoutEvent->getRequest();
        $redirectUrl = $request->query->get('redirect_url');
        if ($redirectUrl) {
            $logoutEvent->setResponse(
                new RedirectResponse($redirectUrl)
            );
        }
    }
}
