<?php

namespace Runalyze\Bundle\CoreBundle\Services\Recalculation;

use App\Entity\Account;
use App\Repository\RaceresultRepository;
use App\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\MarathonShapeCalculation;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\StartTimeCalculation;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\VO2maxCorrectionFactorCalculation;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\VO2maxShapeCalculation;

class RecalculationManager
{
    /** @var RecalculationTaskCollection */
    protected $Tasks;

    /** @var array */
    protected $AccountRelatedTaskNames = [];

    /** @var array [accountId => startTime] */
    protected $CurrentStartTimes = [];

    /** @var ConfigurationManager */
    protected $ConfigurationManager;

    /** @var ConfigurationUpdater */
    protected $ConfigurationUpdater;

    /** @var TrainingRepository */
    protected $TrainingRepository;

    /** @var RaceresultRepository */
    protected $RaceResultRepository;

    /** @var VO2maxShapeCalculation */
    protected $vo2maxShapeCalculation;

    /** @var MarathonShapeCalculation */
    protected $marathonShapeCalculation;

    /** @var VO2maxCorrectionFactorCalculation */
    protected $vo2maxCorrectionFactorCalculation;

    public function __construct(
        ConfigurationManager $manager,
        ConfigurationUpdater $updater,
        TrainingRepository $trainingRepository,
        RaceresultRepository $raceresultRepository,
        VO2maxShapeCalculation $vo2maxShapeCalculation,
        MarathonShapeCalculation $marathonShapeCalculation,
        VO2maxCorrectionFactorCalculation $vo2maxCorrectionFactorCalculation
    )
    {
        $this->ConfigurationManager = $manager;
        $this->ConfigurationUpdater = $updater;
        $this->TrainingRepository = $trainingRepository;
        $this->RaceResultRepository = $raceresultRepository;
        $this->vo2maxShapeCalculation = $vo2maxShapeCalculation;
        $this->marathonShapeCalculation = $marathonShapeCalculation;
        $this->vo2maxCorrectionFactorCalculation = $vo2maxCorrectionFactorCalculation;
        $this->Tasks = new RecalculationTaskCollection();
    }

    /**
     * @param Account $account
     * @return int
     */
    public function getNumberOfScheduledTasksFor(Account $account)
    {
        if (!isset($this->AccountRelatedTaskNames[$account->getId()])) {
            return 0;
        }

        return count($this->AccountRelatedTaskNames[$account->getId()]);
    }

    /**
     * @param Account $account
     * @param int $timestamp
     * @param bool $isRemoved
     */
    public function addStartTimeCheck(Account $account, $timestamp, $isRemoved)
    {
        $currentStartTime = $this->getCurrentStartTime($account);

        if ($isRemoved) {
            if ($timestamp <= $currentStartTime) {
                $this->scheduleStartTimeCalculation($account, true);
            }
        } else {
            if ($timestamp < $currentStartTime) {
                $task = $this->scheduleStartTimeCalculation($account, false);
                $task->setNewStartTime($timestamp);
            }
        }
    }

    /**
     * @param Account $account
     * @return int
     */
    protected function getCurrentStartTime(Account $account)
    {
        if (!isset($this->CurrentStartTimes[$account->getId()])) {
            $this->CurrentStartTimes[$account->getId()] = $this->ConfigurationManager->getList($account)->getData()->getStartTime();

            if (0 == $this->CurrentStartTimes[$account->getId()]) {
                $this->CurrentStartTimes[$account->getId()] = PHP_INT_MAX;
            }
        }

        return $this->CurrentStartTimes[$account->getId()];
    }

    /**
     * @param Account $account
     * @param bool $forceRecalculation
     * @return StartTimeCalculation
     */
    public function scheduleStartTimeCalculation(Account $account, $forceRecalculation = true)
    {
        if (!$this->isTaskScheduled($account, StartTimeCalculation::class)) {
            $task = new StartTimeCalculation($this->TrainingRepository, $this->ConfigurationUpdater);
            $this->scheduleTaskForAccount($account, $task);
        } else {
            /** @var StartTimeCalculation $task */
            $task = $this->Tasks->offsetGet($this->AccountRelatedTaskNames[$account->getId()][StartTimeCalculation::class]);
        }

        if ($forceRecalculation) {
            $task->forceRecalculation();
        }

        return $task;
    }

    public function scheduleEffectiveVO2maxCorrectionFactorCalculation(Account $account)
    {
        if (!$this->isTaskScheduled($account, VO2maxCorrectionFactorCalculation::class)) {
            $this->scheduleTaskForAccount($account, $this->vo2maxCorrectionFactorCalculation);
        }
    }

    public function scheduleEffectiveVO2maxShapeCalculation(Account $account)
    {
        if (!$this->isTaskScheduled($account, VO2maxShapeCalculation::class)) {
            $this->scheduleTaskForAccount($account, $this->vo2maxShapeCalculation);
            $this->scheduleMarathonShapeCalculation($account);
        }
    }

    public function scheduleMarathonShapeCalculation(Account $account)
    {
        if (!$this->isTaskScheduled($account, MarathonShapeCalculation::class)) {
            $this->scheduleTaskForAccount($account, $this->marathonShapeCalculation);
        }
    }

    /**
     * @param Account $account
     * @param string $taskName
     * @return bool
     */
    public function isTaskScheduled(Account $account, $taskName)
    {
        $accountId = $account->getId();

        if (!isset($this->AccountRelatedTaskNames[$accountId])) {
            $this->AccountRelatedTaskNames[$accountId] = [];
        }

        return isset($this->AccountRelatedTaskNames[$accountId][$taskName]);
    }

    protected function scheduleTaskForAccount(Account $account, RecalculationTaskInterface $task)
    {
        $accountId = $account->getId();
        $taskName = get_class($task);

        $task->setAccount($account);

        $this->AccountRelatedTaskNames[$accountId][$taskName] = $this->Tasks->addTask($task);
    }

    public function runScheduledTasks()
    {
        $this->Tasks->runAllTasks();
        $this->Tasks->clear();

        $this->AccountRelatedTaskNames = [];
        $this->CurrentStartTimes = [];
    }
}
