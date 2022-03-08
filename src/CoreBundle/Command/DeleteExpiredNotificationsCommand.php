<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class DeleteExpiredNotificationsCommand extends ContainerAwareCommand
{
    /** @var NotificationRepository */
    protected $notificationRepository;

    public function __construct(
        NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:notifications:clear')
            ->setDescription('Delete all expired notifications')
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
        $numRemoved = $this->notificationRepository->removeExpiredNotifications();

        $output->writeln(sprintf('<info>%u notifications have been removed.</info>', $numRemoved));
        $output->writeln('');

        return 0;
    }
}
