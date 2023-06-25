<?php

namespace Runalyze\Bundle\CoreBundle\Tests\DataFixtures;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\ORM\LoadAccountData;

abstract class AbstractFixturesAwareWebTestCase extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected EntityManager $EntityManager;
    protected ?array $FixtureClasses = null;
    protected ReferenceRepository $Fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        /** @var EntityManager */
        $this->EntityManager = self::$container->get('doctrine')->getManager();

        if (null === $this->FixtureClasses) {
            $this->FixtureClasses = [
                LoadAccountData::class
            ];
        }

        /** @var AbstractDatabaseTool */
        $this->databaseTool = self::$container->get(DatabaseToolCollection::class)->get();
        $this->Fixtures = $this->databaseTool->loadFixtures($this->FixtureClasses)->getReferenceRepository();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (null !== $this->EntityManager) {
            $this->EntityManager->close();
            unset($this->EntityManager);
        }

        unset($this->databaseTool);
    }

    protected function getDefaultAccount(): Account
    {
        return $this->Fixtures->getReference('account-default');
    }

    protected function getDefaultAccountsRunningSport(): Sport
    {
        return $this->Fixtures->getReference('account-default.sport-running');
    }

    protected function getDefaultAccountsCyclingSport(): Sport
    {
        return $this->Fixtures->getReference('account-default.sport-cycling');
    }

    protected function getDefaultAccountsClothesType(): EquipmentType
    {
        return $this->Fixtures->getReference('account-default.equipment-type-clothes');
    }

    protected function getEmptyAccount(): Account
    {
        return $this->Fixtures->getReference('account-empty');
    }

    protected function getActivityForDefaultAccount(
        int $timestamp = null,
        int|float $duration = 3600,
        float|int $distance = null,
        Sport $sport = null
    ): Training
    {
        return (new Training())
            ->setS($duration)
            ->setTime($timestamp ?: time())
            ->setDistance($distance)
            ->setSport($sport ?: $this->getDefaultAccountsRunningSport())
            ->setAccount($this->getDefaultAccount());
    }
}
