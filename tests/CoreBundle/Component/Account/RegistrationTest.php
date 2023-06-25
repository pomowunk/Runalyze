<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Account;

use Doctrine\ORM\EntityManager;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group requiresKernel
 * @group requiresDoctrine
 */
class RegistrationTest extends KernelTestCase
{
    protected EntityManager $EntityManager;
    protected AccountRepository $AccountRepository;
    protected SportRepository $SportRepository;
    protected EquipmentTypeRepository $EquipmentTypeRepository;
    protected AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->databaseTool = self::$container->get(DatabaseToolCollection::class)->get();
        $this->databaseTool->loadFixtures([]);

        $this->EntityManager = self::$container->get('doctrine')->getManager();

        $this->AccountRepository = $this->EntityManager->getRepository(Account::class);
        $this->SportRepository = $this->EntityManager->getRepository(Sport::class);
        $this->EquipmentTypeRepository = $this->EntityManager->getRepository(EquipmentType::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }

    public function testThatRegistrationProcessWorks()
    {
        $account = new Account();
        $account->setUsername('foobar');
        $account->setMail('foo@bar.com');

        $registration = new Registration($this->EntityManager, $account, $this->SportRepository, $this->EquipmentTypeRepository);
        $registration->requireAccountActivation();
        $registration->setPassword('Pa$$w0rd', self::$container->get('security.encoder_factory'));
        $registeredAccount = $registration->registerAccount();

        $this->assertEquals('foobar', $this->AccountRepository->find($registeredAccount->getId())->getUsername());
        $this->assertNotNull($registeredAccount->getActivationHash());
    }
}
