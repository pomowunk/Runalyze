<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Runalyze\Bundle\CoreBundle\Command\InstallDatabaseCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @backupGlobals enabled
 *
 * @group requiresKernel
 * @group requiresDoctrine
 */
class InstallDatabaseCommandTest extends KernelTestCase
{
    /** @var \Doctrine\DBAL\Connection */
    protected $Connection;

    /** @var string */
    protected $DatabasePrefix = 'test_empty_';

    protected function setUp(): void
    {
        $_ENV['DATABASE_PREFIX'] = $this->DatabasePrefix;
        static::bootKernel();
        if($this->DatabasePrefix !== static::$container->getParameter('app.database_prefix')) {
            $this->markTestSkipped('Failed to override the database prefix.');
        }

        $this->Connection = static::$kernel->getContainer()->get('doctrine')->getConnection();
        if (null === $this->Connection) {
            $this->markTestSkipped('No doctrine connection available, maybe cache needs to be cleared.');
        }

        $this->dropAllTables();
    }

    protected function tearDown(): void
    {
        $this->dropAllTables();

        parent::tearDown();
    }

    protected function dropAllTables()
    {
        if (null !== $this->Connection) {
            $stmt = $this->Connection->executeQuery('SHOW TABLES LIKE "'.$this->DatabasePrefix.'%"');
            $tables = $stmt->fetchFirstColumn();

            if (!empty($tables)) {
                $this->Connection->executeStatement('SET foreign_key_checks = 0');
                $this->Connection->executeQuery('DROP TABLE `'.implode('`, `', $tables).'`');
                $this->Connection->executeStatement('SET foreign_key_checks = 1');
            }
        }
    }

    public function testExecute()
    {
        $application = new Application(static::$kernel);
        $application->add(self::$container->get(InstallDatabaseCommand::class));

        $command = $application->find('runalyze:install:database');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(23, $this->Connection->executeQuery(
            'SHOW TABLES LIKE "'.$this->DatabasePrefix.'%"'
        )->rowCount());

        $this->assertMatchesRegularExpression('/Database has been successfully initialized./', $commandTester->getDisplay());
    }
}
