<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class CleanupPostersCommand extends Command
{
    protected static $defaultName = 'runalyze:cleanup:posters';

    protected Filesystem $filesystem;
    protected string $posterExportDirectory;
    protected string $posterStoragePeriod;

    public function __construct(
        Filesystem $filesystem,
        string $posterExportDirectory,
        string $posterStoragePeriod)
    {
        $this->filesystem = $filesystem;
        $this->posterExportDirectory = $posterExportDirectory;
        $this->posterStoragePeriod = $posterStoragePeriod;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Cleanup posters older than parameter: poster_storage_period')
            ->addArgument('days', InputArgument::OPTIONAL, 'min. age posters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = $input->getArgument('days') ?: $this->posterStoragePeriod;
        $output->writeln(sprintf('<info>Delete all posters older than %s days</info>', $days));

        $finder = new Finder();

        $deleted = 0;
        if (is_dir($this->posterExportDirectory)) {
            $finder
                ->files()
                ->name('*.png')
                ->in($this->posterExportDirectory)
                ->date(sprintf('until %s days ago', $days));

            $deleted= $finder->count();

            foreach ($finder as $file) {
                $this->filesystem->remove($file);
            }
        }

        $output->writeln(sprintf('<info>%s deleted posters</info>', $deleted));
        $output->writeln('');

        return 0;
    }
}
