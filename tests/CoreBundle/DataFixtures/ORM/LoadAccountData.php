<?php

namespace Runalyze\Bundle\CoreBundle\Tests\DataFixtures\ORM;

use App\Entity\Account;
use App\Entity\Sport;
use App\Entity\EquipmentType;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class LoadAccountData extends AbstractFixture implements OrderedFixtureInterface, ORMFixtureInterface
{
    protected EncoderFactoryInterface $encoderFactory;
    public static Account $emptyAccount;
    public static Account $defaultAccount;

    public function __construct(EncoderFactoryInterface $encoderFactory) {
        $this->encoderFactory = $encoderFactory;
    }

    public function load(ObjectManager $manager)
    {
        $this->addEmptyAccount($manager);
        $this->registerDefaultAccount($manager);
    }

    protected function addEmptyAccount(ObjectManager $manager)
    {
        static::$emptyAccount = new Account();
        static::$emptyAccount->setUsername('empty');
        static::$emptyAccount->setMail('empty@test.com');

        $encoder = $this->encoderFactory->getEncoder(static::$emptyAccount);
        static::$emptyAccount->setPassword($encoder->encodePassword('emptyPassword', static::$emptyAccount->getSalt()));

        $manager->persist(static::$emptyAccount);
        $manager->flush();

        $this->addReference('account-empty', static::$emptyAccount);
    }

    protected function registerDefaultAccount(ObjectManager $manager)
    {
        static::$defaultAccount = new Account();
        static::$defaultAccount->setUsername('default');
        static::$defaultAccount->setMail('default@test.com');

        $registration = $this->registerAccount($manager, static::$defaultAccount, 'defaultPassword');

        $this->addReference('account-default', static::$defaultAccount);
        $this->addReference('account-default.sport-running', $registration->getRegisteredSportForRunning());
        $this->addReference('account-default.sport-cycling', $registration->getRegisteredSportForCycling());
        $this->addReference('account-default.equipment-type-clothes', $registration->getRegisteredEquipmentTypeClothes());
    }

    protected function registerAccount(ObjectManager $manager, Account $account, $password)
    {
        $sportRepo = $manager->getRepository(Sport::class);
        $equipmentTypeRepo = $manager->getRepository(EquipmentType::class);
        
        $registration = new Registration($manager, $account, $sportRepo, $equipmentTypeRepo);
        $registration->setPassword($password, $this->encoderFactory);
        $registration->registerAccount();

        return $registration;
    }

    public function getOrder()
    {
        return 1;
    }
}
