<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Backup;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\AbstractBackup;

/**
 * @group dependsOn
 * @group dependsOnOldDatabase
 */

class AbstractBackupTest extends TestCase
{
    /** @var string */
    const TESTFILE = '/../../../../../data/backup-tool/backup/test.json.gz';

    /** @var AbstractBackup */
    protected $Backup;

    public function setUp(): void
    {
        $mockBuilder = $this->getMockBuilder(AbstractBackup::class);
        $mockBuilder->setConstructorArgs([__DIR__.self::TESTFILE, 1, \DB::getInstance(), PREFIX, '3.2.0']);
        $this->Backup = $mockBuilder->getMockForAbstractClass();
    }

    public function tearDown(): void
    {
        if (file_exists(__DIR__.self::TESTFILE)) {
            unlink(__DIR__.self::TESTFILE);
        }
    }

    public function testThatBackupQueriesDoWork()
    {
        $this->expectNotToPerformAssertions();

        $this->Backup->run();
    }
}
