<?php

namespace App\Tests\Repository;

use App\Entity\Account;
use App\Entity\Raceresult;
use App\Entity\Training;
use App\Repository\RaceresultRepository;
use App\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\Task\VO2maxCorrectionFactorCalculation;

/**
 * @group requiresDoctrine
 */
class RaceresultRepositoryTest extends AbstractRepositoryTestCase
{
    /** @var RaceresultRepository */
    protected $RaceresultRepository;

    /** @var TrainingRepository */
    protected $TrainingRepository;

    /** @var Account */
    protected $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->RaceresultRepository = $this->EntityManager->getRepository(Raceresult::class);
        $this->TrainingRepository = $this->EntityManager->getRepository(Training::class);
        $this->Account = $this->getDefaultAccount();
    }

    public function testEmptyDatabase()
    {
        $this->assertNull($this->RaceresultRepository->findByActivity(1));
        $this->assertNull($this->RaceresultRepository->findForAccount(1, 1));

        $this->assertEqualsWithDelta(1.0, $this->RaceresultRepository->getEffectiveVO2maxCorrectionFactor(
                $this->Account,
                $this->getDefaultAccountsRunningSport()->getId()
            ), 1e-6
        );
    }

    public function testSingleRace()
    {
        $raceActivity = $this->getActivityForDefaultAccount(mktime(3, 14, 15, 9, 26, 2016), 2400, 10.0);
        $race = $this->insertRace('Awesome pirace', $raceActivity);

        $this->assertNull($this->RaceresultRepository->findForAccount($race->getActivity()->getId(), $this->Account->getId() + 1));
        $this->assertEquals(
            $this->RaceresultRepository->findByActivity($race->getActivity()->getId()),
            $this->RaceresultRepository->findForAccount($race->getActivity()->getId(), $this->Account->getId())
        );
        $this->assertEquals(2400, $this->RaceresultRepository->findForAccount($race->getActivity()->getId(), $this->Account->getId())->getOfficialTime());

        $this->assertEmpty($this->RaceresultRepository->findBySportAndYear($this->Account, $this->getDefaultAccountsRunningSport(), 2017));
        $this->assertEmpty($this->RaceresultRepository->findBySportAndYear($this->Account, $this->getDefaultAccountsCyclingSport(), 2016));
        $this->assertEmpty($this->RaceresultRepository->findBySportAndYear(new Account(), $this->getDefaultAccountsRunningSport(), 2016));

        $races = $this->RaceresultRepository->findBySportAndYear($this->Account, $this->getDefaultAccountsRunningSport(), 2016);

        $this->assertEquals(1, count($races));
        $this->assertEquals(mktime(3, 14, 15, 9, 26, 2016), $races[0]['time']);
        $this->assertEquals('10', $races[0][0]['officialDistance']);
        $this->assertEquals('Awesome pirace', $races[0][0]['name']);

        $this->assertFalse(self::$container->get(RecalculationManager::class)->isTaskScheduled($this->Account, VO2maxCorrectionFactorCalculation::class));
    }

    public function testFindingVO2maxCorrectionFactorForSingleRace()
    {
        $activity = $this->getActivityForDefaultAccount(time(), 2400, 10.0);
        $activity->setPulseAvg(160)->setUseVO2max(true);
        $this->insertRace('foobar', $activity);

        $expectedFactor = $activity->getVO2maxByTime() / $activity->getVO2max();

        $this->assertEqualsWithDelta($expectedFactor, $this->RaceresultRepository->getEffectiveVO2maxCorrectionFactor(
                $this->Account,
                $this->getDefaultAccountsRunningSport()->getId()
            ), 0.01
        );

        $this->assertTrue(self::$container->get(RecalculationManager::class)->isTaskScheduled($this->Account, VO2maxCorrectionFactorCalculation::class));
    }

    public function testFindingVO2maxCorrectionFactorForMultipleRaces()
    {
        $activity = $this->getActivityForDefaultAccount(time(), 2400, 10.0);
        $activity->setPulseAvg(160)->setUseVO2max(true);
        $this->insertRace('foobar', $activity);

        $activity = $this->getActivityForDefaultAccount(time(), 2100, 10.0);
        $activity->setPulseAvg(140)->setUseVO2max(true);
        $this->insertRace('foobar', $activity);

        $activity = $this->getActivityForDefaultAccount(time(), 1200, 5.0);
        $activity->setPulseAvg(180)->setUseVO2max(true);
        $this->insertRace('foobar', $activity);
        $expectedFactor = $activity->getVO2maxByTime() / $activity->getVO2max();

        $activity = $this->getActivityForDefaultAccount(time(), 2400, 5.0);
        $activity->setPulseAvg(100)->setUseVO2max(true);
        $this->insertRace('foobar', $activity);

        $this->assertEqualsWithDelta($expectedFactor, $this->RaceresultRepository->getEffectiveVO2maxCorrectionFactor(
                $this->Account,
                $this->getDefaultAccountsRunningSport()->getId()
            ), 0.01
        );
    }

    /**
     * @param string $name
     * @param Training $activity
     * @return Raceresult
     */
    protected function insertRace($name, Training $activity)
    {
        $this->TrainingRepository->save($activity);

        $race = new Raceresult();
        $race->fillFromActivity($activity);
        $race->setName($name);

        $this->RaceresultRepository->save($race);

        return $race;
    }
}
