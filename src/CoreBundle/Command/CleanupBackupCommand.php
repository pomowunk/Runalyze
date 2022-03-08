<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CleanupBackupCommand extends ContainerAwareCommand
{
    /** @var Filesystem */
    protected $filesystem;

    /** @var string */
    protected $dataDirectory;

    /** @var string */
    protected $backupStoragePeriod;

    public function __construct(Filesystem $filesystem, string $dataDirectory, string $backupStoragePeriod)
    {
        $this->filesystem = $filesystem;
        $this->dataDirectory = $dataDirectory;
        $this->backupStoragePeriod = $backupStoragePeriod;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:cleanup:backups')
            ->setDescription('Cleanup user backups older than parameter: backup_storage_period')
            ->addArgument('days', InputArgument::OPTIONAL, 'min. age backups')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $days = $input->getArgument('days') ?: $this->backupStoragePeriod;
        $output->writeln(sprintf('<info>Delete all backups older than %s days</info>', $days));

        $finder = new Finder();
        $finder
            ->files()
            ->name('*.gz')
            ->in($this->dataDirectory.'/backup-tool/backup')
            ->date(sprintf('until %s days ago', $days));

        $deleted= $finder->count();

        foreach ($finder as $file) {
            $this->filesystem->remove($file);
        }

        $output->writeln(sprintf('<info>%s deleted backups</info>', $deleted));
        $output->writeln('');

        return 0;
    }
}
