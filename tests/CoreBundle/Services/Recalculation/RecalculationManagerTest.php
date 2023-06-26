<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Recalculation;

use App\Entity\Account;
use App\Repository\ConfRepository;
use App\Repository\RaceresultRepository;
use App\Repository\TrainingRepository;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\MarathonShapeCalculation;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\VO2maxCorrectionFactorCalculation;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\VO2maxShapeCalculation;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class RecalculationManagerTest extends TestCase
{
    /** @var  RecalculationManager */
    protected $Manager;

    /** @var  Account */
    protected $Account;

    /** @var  TrainingRepository */
    protected $TrainingRepository;

    /** @var  RaceResultRepository */
    protected $RaceResultRepository;

    /** @var ConfigurationManager */
    protected $ConfigurationManager;

    /** @var ConfigurationUpdater */
    protected $ConfigurationUpdater;

    public function setUp(): void
    {
        $this->Account = new Account();
        $confRepository = $this->getConfRepositoryMock();
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new PreAuthenticatedToken($this->Account, 'foo', 'bar'));

        $this->ConfigurationManager = new ConfigurationManager($confRepository, $tokenStorage);
        $list = $this->ConfigurationManager->getList();
        $list->set('data.START_TIME', '0');
        $list->set('data.VO2MAX_FORM', '0');
        $list->set('data.VO2MAX_CORRECTOR', '1.00');
        $list->set('data.BASIC_ENDURANCE', '0');

        $this->TrainingRepository = $this->getTrainingRepositoryMock();
        $this->RaceResultRepository = $this->getRaceResultRepositoryMock();
        $this->ConfigurationUpdater = new ConfigurationUpdater($confRepository, $this->ConfigurationManager);
        
        $this->Manager = new RecalculationManager(
            $this->ConfigurationManager,
            $this->ConfigurationUpdater,
            $this->TrainingRepository,
            $this->RaceResultRepository,
            $this->getVO2maxShapeCalculationMock(),
            $this->getMarathonShapeCalculationMock(),
            $this->getVO2maxCorrectionFactorCalculationMock()
        );
    }

    public function testNoScheduledTasks()
    {
        $this->assertEquals(0, $this->Manager->getNumberOfScheduledTasksFor($this->Account));

        $this->Manager->runScheduledTasks();

        $this->assertEquals('0', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
        $this->assertEquals('0', $this->ConfigurationManager->getList($this->Account)->get('data.VO2MAX_FORM'));
        $this->assertEquals('1.00', $this->ConfigurationManager->getList($this->Account)->get('data.VO2MAX_CORRECTOR'));
        $this->assertEquals('0', $this->ConfigurationManager->getList($this->Account)->get('data.BASIC_ENDURANCE'));
    }

    public function testThatMarathonShapeHasToBeCalculatedIfVO2maxShapeIsCalculated()
    {
        $this->Manager->scheduleEffectiveVO2maxShapeCalculation($this->Account);

        $this->assertEquals(2, $this->Manager->getNumberOfScheduledTasksFor($this->Account));
    }

    public function testThatTasksAreNotScheduledMultipleTimes()
    {
        $this->Manager->scheduleStartTimeCalculation($this->Account);
        $this->Manager->scheduleStartTimeCalculation($this->Account);
        $this->Manager->scheduleMarathonShapeCalculation($this->Account);
        $this->Manager->scheduleMarathonShapeCalculation($this->Account);
        $this->Manager->scheduleEffectiveVO2maxShapeCalculation($this->Account);
        $this->Manager->scheduleEffectiveVO2maxShapeCalculation($this->Account);
        $this->Manager->scheduleMarathonShapeCalculation($this->Account);

        $this->assertEquals(3, $this->Manager->getNumberOfScheduledTasksFor($this->Account));
    }

    public function testThatResultsOfTasksAreForwardedToConfiguration()
    {
        $this->Manager->scheduleEffectiveVO2maxShapeCalculation($this->Account);
        $this->Manager->scheduleMarathonShapeCalculation($this->Account);
        $this->Manager->scheduleStartTimeCalculation($this->Account);
        $this->Manager->scheduleEffectiveVO2maxCorrectionFactorCalculation($this->Account);

        $this->assertEquals(4, $this->Manager->getNumberOfScheduledTasksFor($this->Account));

        $this->Manager->runScheduledTasks();

        $this->assertEquals('123456789', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
        $this->assertEquals('35.7', $this->ConfigurationManager->getList($this->Account)->get('data.VO2MAX_FORM'));
        $this->assertEquals('0.85', $this->ConfigurationManager->getList($this->Account)->get('data.VO2MAX_CORRECTOR'));
        $this->assertEquals('68', $this->ConfigurationManager->getList($this->Account)->get('data.BASIC_ENDURANCE'));
    }

    public function testThatStartTimeCanBeUpdatedWithoutFullRecalculation()
    {
        $this->Manager->addStartTimeCheck($this->Account, 100000000, false);
        $this->Manager->runScheduledTasks();

        $this->assertEquals('100000000', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
    }

    public function testThatNewStartTimeAboveCurrentValueDoesNotChangeAnything()
    {
        $this->ConfigurationUpdater->updateStartTime($this->Account, 100000000);

        $this->Manager->addStartTimeCheck($this->Account, 123456789, false);
        $this->Manager->addStartTimeCheck($this->Account, 154321000, false);
        $this->Manager->runScheduledTasks();

        $this->assertEquals('100000000', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
    }

    public function testMultipleStartTimeUpdates()
    {
        $this->ConfigurationUpdater->updateStartTime($this->Account, 100000000);

        $this->Manager->addStartTimeCheck($this->Account, 123456789, false);
        $this->Manager->addStartTimeCheck($this->Account, 154321000, false);
        $this->Manager->addStartTimeCheck($this->Account, 98765432, false);
        $this->Manager->addStartTimeCheck($this->Account, 23456789, false);
        $this->Manager->runScheduledTasks();

        $this->assertEquals('23456789', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
    }

    public function testThatStartTimeIsNotChangedIfRemovedActivityIsTooNew()
    {
        $this->ConfigurationUpdater->updateStartTime($this->Account, 100000000);

        $this->Manager->addStartTimeCheck($this->Account, 100000001, true);
        $this->Manager->runScheduledTasks();

        $this->assertEquals('100000000', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
    }

    public function testThatStartTimeIsChangedIfRemovedActivityWasTheOldestOne()
    {
        $this->ConfigurationUpdater->updateStartTime($this->Account, 100000000);

        $this->Manager->addStartTimeCheck($this->Account, 100000000, true);
        $this->Manager->runScheduledTasks();

        $this->assertEquals('123456789', $this->ConfigurationManager->getList($this->Account)->get('data.START_TIME'));
    }

    protected function getAccountMock()
    {
        $account = $this
            ->getMockBuilder(Account::class)
            ->onlyMethods(['getId'])
            ->getMock();

        $account
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        return $account;
    }

    /**
     * @return TrainingRepository
     */
    protected function getTrainingRepositoryMock()
    {
        $repository = $this
            ->getMockBuilder(TrainingRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStartTime', 'calculateVO2maxShape', 'calculateMarathonShape'])
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('getStartTime')
            ->will($this->returnValue(123456789));

        $repository
            ->expects($this->any())
            ->method('calculateVO2maxShape')
            ->will($this->returnValue(42.0));

        $repository
            ->expects($this->any())
            ->method('calculateMarathonShape')
            ->will($this->returnValue(68));

        return $repository;
    }

    /**
     * @return RaceresultRepository
     */
    protected function getRaceResultRepositoryMock()
    {
        $repository = $this
            ->getMockBuilder(RaceresultRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEffectiveVO2maxCorrectionFactor'])
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('getEffectiveVO2maxCorrectionFactor')
            ->will($this->returnValue(0.85));

        return $repository;
    }

    /**
     * @return ConfRepository
     */
    protected function getConfRepositoryMock()
    {
        $repository = $this
            ->getMockBuilder(ConfRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByAccount', 'findByAccountAndKey', 'save'])
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('findByAccount')
            ->will($this->returnValue([]));

        $repository
            ->expects($this->any())
            ->method('findByAccountAndKey')
            ->will($this->returnValue(null));

        $repository
            ->expects($this->any())
            ->method('save')
            ->will($this->returnValue(null));

        return $repository;
    }

    /**
     * @return VO2maxShapeCalculation
     */
    protected function getVO2maxShapeCalculationMock()
    {
        $mock = $this
            ->getMockBuilder(VO2maxShapeCalculation::class)
            ->onlyMethods([])
            ->setConstructorArgs([$this->TrainingRepository, $this->ConfigurationManager, $this->ConfigurationUpdater])
            ->getMock();

        return $mock;
    }

    /**
     * @return MarathonShapeCalculation
     */
    protected function getMarathonShapeCalculationMock()
    {
        $mock = $this
            ->getMockBuilder(MarathonShapeCalculation::class)
            ->onlyMethods([])
            ->setConstructorArgs([$this->TrainingRepository, $this->ConfigurationManager, $this->ConfigurationUpdater])
            ->getMock();

        return $mock;
    }

    /**
     * @return VO2maxCorrectionFactorCalculation
     */
    protected function getVO2maxCorrectionFactorCalculationMock()
    {
        $mock = $this
            ->getMockBuilder(VO2maxCorrectionFactorCalculation::class)
            ->onlyMethods([])
            ->setConstructorArgs([$this->RaceResultRepository, $this->ConfigurationManager, $this->ConfigurationUpdater])
            ->getMock();

        return $mock;
    }
}
