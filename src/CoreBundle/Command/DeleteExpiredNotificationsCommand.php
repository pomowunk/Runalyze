<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use App\Repository\NotificationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteExpiredNotificationsCommand extends Command
{
    protected static $defaultName = 'runalyze:notifications:clear';

    protected NotificationRepository $notificationRepository;

    public function __construct(
        NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Delete all expired notifications')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numRemoved = $this->notificationRepository->removeExpiredNotifications();

        $output->writeln(sprintf('<info>%u notifications have been removed.</info>', $numRemoved));
        $output->writeln('');

        return 0;
    }
}
