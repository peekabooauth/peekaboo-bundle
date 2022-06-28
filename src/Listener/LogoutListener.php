<?php

namespace Peekabooauth\PeekabooBundle\Listener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $logoutEvent): void
    {
        $request = $logoutEvent->getRequest();
        if ($request->get('redirect_url')) {
            $logoutEvent->setResponse(new RedirectResponse(
                $request->get('redirect_url'),
                Response::HTTP_MOVED_PERMANENTLY)
            );
        }
    }
}
