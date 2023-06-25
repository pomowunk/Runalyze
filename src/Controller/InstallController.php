<?php

namespace App\Controller;

use Runalyze\Bundle\CoreBundle\Console\Formatter\HtmlOutputFormatter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class InstallController extends AbstractController
{
    /**
     * @Route("/install", name="install", condition="'%app.allow_update%' == true")
    */
    public function installAction(Request $request, KernelInterface $kernel): Response
    {
        $session = $request->getSession();

        if ($session->has('installer/successful')) {
            return $this->redirectToRoute('install_finish');
        }

        $app = new Application($kernel);
        $app->setAutoExit(false);

        $input = new StringInput('runalyze:install:check');
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true, new HtmlOutputFormatter(true));
        $exitCode = $app->run($input, $output);

        $session->set('installer/possible', $exitCode == 0);

        return $this->render('system/install.html.twig', [
            'output' => '$ php bin/console runalyze:install:check'."\n\n".$output->fetch(),
            'installationPossible' => $exitCode == 0,
            'installationSuccessful' => false
        ]);
    }

    /**
     * @Route("/install/start", name="install_start", condition="'%app.allow_update%' == true")
     */
    public function startAction(Request $request, KernelInterface $kernel): Response
    {
        $session = $request->getSession();

        if (!$session->has('installer/possible') || !$session->get('installer/possible')) {
            return $this->redirectToRoute('install');
        }

        if ($session->has('installer/successful')) {
            return $this->redirectToRoute('install_finish');
        }

        $app = new Application($kernel);
        $app->setAutoExit(false);

        $input = new StringInput('runalyze:install --skip=check');
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true, new HtmlOutputFormatter(true));
        $exitCode = $app->run($input, $output);
        $outputString = '$ php bin/console runalyze:install --skip=check'."\n\n".$output->fetch();

        if ($exitCode > 0) {
            return $this->render('system/install.html.twig', [
                'output' => $outputString,
                'installationPossible' => false,
                'installationSuccessful' => false
            ]);
        }

        $session->set('installer/successful', true);
        $session->set('installer/output', $outputString);

        return $this->redirectToRoute('install_finish');
    }

    /**
     * @Route("/install/finish", name="install_finish", condition="'%app.allow_update%' == true")
     */
    public function finishAction(Request $request): Response
    {
        $session = $request->getSession();

        if (!$session->has('installer/successful')) {
            return $this->redirectToRoute('install');
        }

        return $this->render('system/install.html.twig', [
            'output' => $session->get('installer/output', ''),
            'installationPossible' => false,
            'installationSuccessful' => true
        ]);
    }
}
