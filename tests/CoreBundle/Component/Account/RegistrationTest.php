<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Account;

use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * @group requiresKernel
 * @group requiresClient
 */
class RegistrationTest extends WebTestCase
{
    use FixturesTrait;
    
    /** @var Client */
    protected $Client;

    /** @var EntityManager */
    protected $EntityManager;

    /** @var AccountRepository */
    protected $AccountRepository;

    /** @var SportRepository */
    protected $SportRepository;

    /** @var EquipmentTypeRepository */
    protected $EquipmentTypeRepository;
    
    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->loadFixtures([])->getReferenceRepository();

        $this->Client = self::$container->get('test.client');
        $this->Client->disableReboot();

        $this->EntityManager = self::$container->get('doctrine')->getManager();
        $this->EntityManager->clear();

        $this->AccountRepository = $this->EntityManager->getRepository('CoreBundle:Account');
        $this->SportRepository = $this->EntityManager->getRepository('CoreBundle:Sport');
        $this->EquipmentTypeRepository = $this->EntityManager->getRepository('CoreBundle:EquipmentType');

        parent::setUp();
    }

    public function testThatRegistrationProcessWorks()
    {
        $account = new Account();
        $account->setUsername('foobar');
        $account->setMail('foo@bar.com');
        
        $registration = new Registration($this->EntityManager, $account, $this->SportRepository, $this->EquipmentTypeRepository);
        $registration->requireAccountActivation();
        $registration->setPassword('Pa$$w0rd', self::$container->get('test.security.encoder_factory'));
        $registeredAccount = $registration->registerAccount();

        $this->assertEquals('foobar', $this->AccountRepository->find($registeredAccount->getId())->getUsername());
        $this->assertNotNull($registeredAccount->getActivationHash());
    }
}
