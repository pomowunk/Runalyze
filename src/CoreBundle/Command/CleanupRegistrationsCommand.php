<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use App\Repository\AccountRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupRegistrationsCommand extends Command
{
    protected static $defaultName = 'runalyze:cleanup:registrations';

    protected AccountRepository $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Delete not activated account (default: older than 7 days).')
            ->addArgument('days', InputArgument::OPTIONAL, 'min. age of not activated accounts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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
