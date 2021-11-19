<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Import;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookup;
use Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookupException;

/**
 * @group dependsOn
 * @group dependsOnTimezoneDatabase
 * @group requiresSqlite
 */

class TimezoneLookupTest extends TestCase
{
    /** @var TimezoneLookup */
    protected $Lookup;

    protected function setUp(): void
    {
        $this->Lookup = new TimezoneLookup(TESTS_ROOT.'/../data/timezone.sqlite', 'mod_spatialite.so');
    }

    public function testSilenceConstructor()
    {
        $lookup = new TimezoneLookup('here/is/no/timezone/database.sqlite', 'mod_spatialite.so');
        $lookup->silentExceptions();

        $this->assertFalse($lookup->isPossible());
        $this->assertNull($lookup->getTimezoneForCoordinate(13.41, 52.52));
    }

    public function testConstructorWithException()
    {
        $this->expectException(TimezoneLookupException::class);

        $lookup = new TimezoneLookup('here/is/no/timezone/database.sqlite', 'mod_spatialite.so');
        $lookup->isPossible();
    }

    public function testConstructorWithWrongExtensionName()
    {
        $this->expectException(TimezoneLookupException::class);

        $lookup = new TimezoneLookup(TESTS_ROOT.'/../data/timezone.sqlite', 'non-existant-extension.so');
        $lookup->isPossible();
    }

    public function testSimpleLocations()
    {
        try {
            $this->assertEquals('Europe/Berlin', $this->Lookup->getTimezoneForCoordinate(13.41, 52.52));
            $this->assertEquals('America/Los_Angeles', $this->Lookup->getTimezoneForCoordinate(-122.420706, 37.776685));
        } catch (TimezoneLookupException $e) {
            $this->markTestSkipped('Timezone lookup is not possible: '.$e->getMessage());
        }
    }

    public function testInvalidLocations()
    {
        try {
            $this->assertNull($this->Lookup->getTimezoneForCoordinate('foo', 'bar'));
        } catch (TimezoneLookupException $e) {
            $this->markTestSkipped('Timezone lookup is not possible: '.$e->getMessage());
        }
    }
}
