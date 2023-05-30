<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group requiresKernel
 * @group requiresDoctrine
 */
class InstallDatabaseCommandTest extends KernelTestCase
{
    /** @var \Doctrine\DBAL\Connection */
    protected $Connection;

    /** @var string */
    protected $DatabasePrefix;

    protected function setUp(): void
    {
        static::bootKernel(['environment' => 'test_empty']);

        $this->Connection = static::$kernel->getContainer()->get('doctrine')->getConnection();
        $this->DatabasePrefix = static::$kernel->getContainer()->getParameter('database_prefix');

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
        $application->add(static::$kernel->getContainer()->get('test.runalyze.installdatabasecommand'));

        $command = $application->find('runalyze:install:database');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(23, $this->Connection->executeQuery(
            'SHOW TABLES LIKE "'.$this->DatabasePrefix.'%"'
        )->rowCount());

        $this->assertMatchesRegularExpression('/Database has been successfully initialized./', $commandTester->getDisplay());
    }
}
