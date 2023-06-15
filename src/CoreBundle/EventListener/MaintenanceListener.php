<?php
namespace Runalyze\Bundle\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class MaintenanceListener
{
    protected KernelInterface $kernel;
    protected Environment $twig;
    protected bool $maintenanceMode;

    public function __construct(
        KernelInterface $kernel, 
        Environment $twig, 
        bool $maintenanceMode,
    ) {
        $this->kernel = $kernel;
        $this->twig = $twig;
        $this->maintenanceMode = $maintenanceMode;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $debug = in_array($this->kernel->getEnvironment(), array('test', 'dev'));
	    $request = $event->getRequest();
	    $routes = array('update', 'install','install_start','install_finish','update_start','admin');

        if ($this->maintenanceMode && !$debug && !in_array($request->get('_route'), $routes)) {
            $content = $this->twig->render('maintenance.html.twig');
            $event->setResponse(new Response($content, 503));
            $event->stopPropagation();
        }
    }
}
