<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class InstallFilesystemCommand extends Command
{
    protected static $defaultName = 'runalyze:install:filesystem';

    protected string $projectDirectory;

    public function __construct(string $projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Setup RUNALYZE file system.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Setup RUNALYZE file system...</info>');
        $output->writeln('');

        $this->tryToCopyHtaccess($output);

        $output->writeln('<info>... done.</info>');
        $output->writeln('');

        return 0;
    }

    protected function tryToCopyHtaccess(OutputInterface $output)
    {
        $output->writeln('  <info>Copying .htaccess.dist to .htaccess ...</info>');

        try {
            $FileSystem = new Filesystem();
            $FileSystem->copy($this->projectDirectory.'.htaccess.dist', $this->projectDirectory.'.htaccess');
        } catch (IOException $e) {
            $output->writeln(sprintf('  <comment>%s</comment>', $e->getMessage()));
        }

        $output->writeln('');
    }
}
