<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CleanupRegistrationsCommand extends ContainerAwareCommand
{
    /** @var AccountRepository */
    protected $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:cleanup:registrations')
            ->setDescription('Delete not activated account (default: older than 7 days).')
            ->addArgument('days', InputArgument::OPTIONAL, 'min. age of not activated accounts')
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
        $days = $input->getArgument('days') ?: 7;

        $output->writeln('<info>Delete all not activated accounts older than '.$days.' days</info>');
        $output->writeln('');

        $delete = $this->accountRepository->deleteNotActivatedAccounts($days);
        $output->writeln('<info>'.$delete.' deleted accounts</info>');
        $output->writeln('');

        return 0;
    }
}
