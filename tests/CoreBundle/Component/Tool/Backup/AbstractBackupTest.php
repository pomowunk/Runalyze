<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Backup;

use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\AbstractBackup;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group dependsOn
 * @group dependsOnOldDatabase
 */

class AbstractBackupTest extends KernelTestCase
{
    /** @var string */
    protected string $testFile;

    /** @var AbstractBackup */
    protected $Backup;

    public function setUp(): void
    {
        static::bootKernel();
        $this->testFile = (string)static::$container->getParameter('app.posterExportDirectory').'/test.json.gz';
        $mockBuilder = $this->getMockBuilder(AbstractBackup::class);
        $mockBuilder->setConstructorArgs([$this->testFile, 1, \DB::getInstance(), PREFIX, '3.2.0']);
        $this->Backup = $mockBuilder->getMockForAbstractClass();
    }

    public function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function testThatBackupQueriesDoWork()
    {
        $this->expectNotToPerformAssertions();

        $this->Backup->run();
    }
}
