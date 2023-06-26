<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    protected static $defaultName = 'runalyze:install';

    protected Application $Application;

    protected array $Commands = [
        [
            'command' => 'check',
            'message' => 'Check requirements'
        ],
        [
            'command' => 'database',
            'message' => 'Setting up the database'
        ],
        [
            'command' => 'filesystem',
            'message' => 'Setting up the file system'
        ]
    ];

    protected function configure()
    {
        $this
            ->setDescription('Install RUNALYZE and setup database.')
            ->addOption(
                'skip',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Steps to skip',
                array('')
            );
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->Application = $this->getApplication();
        $this->Application->setCatchExceptions(false);
        $this->Application->setAutoExit(false);

        $this->removeCommandsThatShouldBeSkipped($input);
    }

    protected function removeCommandsThatShouldBeSkipped(InputInterface $input)
    {
        if ($input->hasOption('skip') && is_array($input->getOption('skip'))) {
            $skippedCommands = $input->getOption('skip');

            foreach ($this->Commands as $i => $command) {
                $this->Commands[$i]['skip'] = in_array($command['command'], $skippedCommands);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Installing RUNALYZE...</info>');
        $output->writeln('');

        foreach ($this->Commands as $step => $command) {
            $output->writeln(sprintf('<comment>Step %d of %d.</comment> <info>%s</info>', $step + 1, count($this->Commands), $command['message']));

            if (!$command['skip']) {
                $subInput = new ArrayInput(['command' => 'runalyze:install:'.$command['command']]);
                $exitCode = $this->Application->run($subInput, $output);
                $output->writeln('');

                if ($exitCode > 0) {
                    return $exitCode;
                }
            } else {
                $output->writeln('skipped');
                $output->writeln('');
            }
        }

        $output->writeln('<info>RUNALYZE has been successfully installed.</info>');

        return 0;
    }
}
