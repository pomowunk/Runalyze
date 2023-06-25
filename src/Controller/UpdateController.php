<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class UpdateController extends AbstractController
{
    /**
     * @Route("/update", name="update", condition="'%app.allow_update%' == true")
     */
    public function updateAction(KernelInterface $kernel, $entity_manager = 'default'): Response
    {
        $output = new BufferedOutput();
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $application->run(new ArrayInput([
            'command' => 'doctrine:migrations:status',
            '--em' => $entity_manager,
        ]), $output);

        if ('prod' == $kernel->getEnvironment()) {
            $application->run(new ArrayInput([
                'command' => 'cache:clear',
                '--env' => 'prod',
                '--no-warmup' => true
            ]), $output);

            $output->writeln(' To warmup the cache, run `php bin/console cache:clear`.');
        } else {
            $output->writeln('');
            $output->writeln(' Runalyze is running in dev environment. Cache can\'t be cleared automatically.');
        }

        $content = $output->fetch();

		$updateAvailable = true;
		if (substr_count($content, 'Already at latest version') == 1) {
		    $updateAvailable = false;
		}

        return $this->render('system/update.html.twig', [
            'updateAvailable' => $updateAvailable,
            'migrationDump' => $content
        ]);
    }

    /**
     * @Route("/update/start", name="update_start", condition="'%app.allow_update%' == true")
     */
    public function updateStartAction(KernelInterface $kernel): Response
    {
        $output = new BufferedOutput();
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $application->run(new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction',
        ]), $output);

        $content = $output->fetch();

		if (strpos($content, 'Could not find any migrations to execute.') || strpos($content, 'No migrations to execute.')) {
		    $migrationStatus = 'uptodate';
		} elseif (strpos($content, 'migrations executed')) {
		    $migrationStatus = 'executed';
		} else {
		    $migrationStatus = false;
		}

        return $this->render('system/update_start.html.twig', [
            'migrationStatus' => $migrationStatus,
	        'migrationDump' => $content
        ]);
    }
}
