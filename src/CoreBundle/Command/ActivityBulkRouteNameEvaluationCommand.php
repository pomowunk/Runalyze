<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Command to update activities with details of a route.
 */
class ActivityBulkRouteNameEvaluationCommand extends ContainerAwareCommand {
    private $override = False;

    protected function configure() {
        $this
            ->setName('runalyze:activity:bulk-routeval')
            ->setDescription('Bulk update of trainings route-name evaluation')
            ->addArgument('username', InputArgument::REQUIRED, 'username')
            ->addArgument('id', InputArgument::REQUIRED, 'Id of activity')
            ->addOption('nexted', null, InputOption::VALUE_NONE, 'Update next activities')
            ->addOption('override', null, InputOption::VALUE_NONE, 'Override existing route informations');
    }

    /**
     * @return TrainingRepository
     */
    protected function getTrainingRepository() {
        return $this->getContainer()->get('doctrine')->getRepository('CoreBundle:Training');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $repository = $this->getContainer()->get('doctrine')->getRepository('CoreBundle:Account');
        $user = $repository->loadUserByUsername($input->getArgument('username'));
        $activityId = $input->getArgument('id');
        $nexted = empty($input->getOption('nexted')) ? 0 : 1;
        $this->override = empty($input->getOption('override')) ? False : True;

        if (null === $user) {
            $output->writeln('<fg=red>Unknown User</>');

            return 1;
        }

        if(empty($this->getContainer()->getParameter('osm_overpass_url'))) {
            $output->writeln('<fg=red>Set parameter osm_overpass_url</>');
            return 1;
        }

        $output->writeln('Start route-name evaluation with id=' . $activityId . ' and nexted=' . $nexted . ', override=' . $this->override);

        do {
            $activity = $this->processActivity($output, $activityId, $user);
            $activityId = $this->getTrainingRepository()->getIdOfNextActivity($activity);
        } while($nexted == 1 && $activityId != null);

        $output->writeln('');
        $output->writeln('Done.');
    }

    protected function processActivity(OutputInterface &$output, int $activityId, Account $user): Training {
        $activity = $this->getTrainingRepository()->findForAccount($activityId, $user);

        if($activity->hasRoute()) {
            if(empty($activity->getRouteName()) || $this->override) {
                $contextAdapterFactory = $this->getContainer()->get('app.activity_context_adapter_factory');
                $context = new ActivityContext($activity, null, null, $activity->getRoute());
                $contextAdapter = $contextAdapterFactory->getAdapterFor($context);

                $updated = $contextAdapter->guessRouteDetails();

                if($updated) {
                    $this->getTrainingRepository()->save($activity);

                    $output->writeln('<fg=green>successfully updated id=' . $activityId . ' / ' . $activity->getTitle() . 
                        ' / ' . $activity->getRouteName() . '</>');
                } else {
                    $output->writeln('<fg=yellow>skip id=' . $activityId . ' / ' . $activity->getTitle() . '</>');
                }

            } else {                
                $output->writeln('<fg=yellow>skip id=' . $activityId . ' / ' . $activity->getTitle() . '</>');
            }
        }

        return $activity;
    }
}