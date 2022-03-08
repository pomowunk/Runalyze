<?php
namespace Runalyze\Bundle\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Templating\EngineInterface;

class MaintenanceListener
{
    /** @var KernelInterface */
    protected $kernel;

    /** @var EngineInterface */
    protected $engine;

    /** @var bool */
    protected $maintenance;

    public function __construct(KernelInterface $kernel, bool $maintenance = false)
    {
        $this->kernel = $kernel;
        $this->maintenance = $maintenance;
    }
    
    /** @required */
    public function setTwig(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $debug = in_array($this->kernel->getEnvironment(), array('test', 'dev'));
	    $request = $event->getRequest();
	    $routes = array('update', 'install','install_start','install_finish','update_start','admin');

        if ($this->maintenance && !$debug && !in_array($request->get('_route'), $routes)) {
            $content = $this->engine->render('maintenance.html.twig');
            $event->setResponse(new Response($content, 503));
            $event->stopPropagation();
        }
    }
}
