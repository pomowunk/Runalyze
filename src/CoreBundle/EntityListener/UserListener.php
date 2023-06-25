<?php

namespace Runalyze\Bundle\CoreBundle\EntityListener;

use App\Entity\Account;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;

class UserListener
{
    /** @var ConfigurationUpdater */
    protected $ConfigurationUpdater;

    /** @var UserRepository */
    protected $UserRepository;

    public function __construct(ConfigurationUpdater $updater, UserRepository $repository)
    {
        $this->ConfigurationUpdater = $updater;
        $this->UserRepository = $repository;
    }

    public function postPersist(User $user, LifecycleEventArgs $args)
    {
        $this->updateHeartRateStatsInDataConfiguration($args, $user->getAccount());
    }

    public function postUpdate(User $user, LifecycleEventArgs $args)
    {
        $this->updateHeartRateStatsInDataConfiguration($args, $user->getAccount());
    }

    public function postRemove(User $user, LifecycleEventArgs $args)
    {
        $this->updateHeartRateStatsInDataConfiguration($args, $user->getAccount());
    }

    protected function updateHeartRateStatsInDataConfiguration(LifecycleEventArgs $args, Account $account)
    {
        if ($args->getEntityManager()->getUnitOfWork()->isScheduledForDelete($account)) {
            return;
        }

        $restingHeartRate = $this->UserRepository->getCurrentRestingHeartRate($account) ?: 60;
        $maximalHeartRate = $this->UserRepository->getCurrentMaximalHeartRate($account) ?: 200;

        $this->ConfigurationUpdater->updateRestingHeartRate($account, $restingHeartRate);
        $this->ConfigurationUpdater->updateMaximalHeartRate($account, $maximalHeartRate);
    }
}
