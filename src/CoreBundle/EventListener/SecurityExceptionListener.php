<?php
namespace Runalyze\Bundle\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;

class SecurityExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof DisabledException) {
            $response = new Response();
            $response->setContent('<html><body><h1>Custom disabled page!</h1></body></html>');

            $event->setResponse($response);
        } elseif ($exception instanceof LockedException) {
            // TODO! investigate whatever this was before..leftover example code?
            $response = new Response();
            $response->setContent('<html><body><h1>Account Locked!</h1></body></html>');

            $event->setResponse($response);
        }
    }
}
