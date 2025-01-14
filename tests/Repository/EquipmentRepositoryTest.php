<?php

namespace App\Tests\Repository;

use App\Entity\Account;
use App\Entity\Equipment;
use App\Entity\Training;
use App\Repository\EquipmentRepository;
use App\Repository\TrainingRepository;

/**
 * @group requiresDoctrine
 */
class EquipmentRepositoryTest extends AbstractRepositoryTestCase
{
    /** @var EquipmentRepository */
    protected $EquipmentRepository;

    /** @var TrainingRepository */
    protected $TrainingRepository;

    /** @var Account */
    protected $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->EquipmentRepository = $this->EntityManager->getRepository(Equipment::class);
        $this->TrainingRepository = $this->EntityManager->getRepository(Training::class);
        $this->Account = $this->getDefaultAccount();
    }

    public function testEmptyDatabase()
    {
        $this->assertEmpty($this->EquipmentRepository->findByTypeId(1, new Account()));
    }

    public function testEquipmentStatistics()
    {
        $clothes = $this->EquipmentRepository->findByTypeId($this->getDefaultAccountsClothesType()->getId(), $this->Account);

        $this->assertNotEmpty($clothes);

        $this->TrainingRepository->save(
            $this->getActivityForDefaultAccount(null, 3600, 10.0, null)
                ->addEquipment($clothes[0])
        );
        $this->TrainingRepository->save(
            $this->getActivityForDefaultAccount(null, 1800, 7.5, null)
                ->addEquipment($clothes[0])
        );
        $this->TrainingRepository->save(
            $this->getActivityForDefaultAccount(null, 3600, 12.0, null)
                ->addEquipment($clothes[1])
        );

        $statistics = $this->EquipmentRepository->getStatisticsForType($this->getDefaultAccountsClothesType()->getId(), $this->Account);

        $this->assertEquals(2, $statistics->getCount());
        $this->assertEquals(2, $statistics->getStatistics()[0]->getNumberOfActivities());
        $this->assertEqualsWithDelta(10.0, $statistics->getStatistics()[0]->getMaximalDistance(), 1e-6);
        $this->assertEquals(240, $statistics->getStatistics()[0]->getMaximalPace());
        $this->assertEquals(1, $statistics->getStatistics()[1]->getNumberOfActivities());
        $this->assertEqualsWithDelta(12.0, $statistics->getStatistics()[1]->getMaximalDistance(), 1e-6);
        $this->assertEquals(300, $statistics->getStatistics()[1]->getMaximalPace());
    }
}
