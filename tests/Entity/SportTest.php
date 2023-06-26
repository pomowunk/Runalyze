<?php

namespace App\Tests\Entity;

use App\Entity\Sport;
use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Profile\Sport\Generic;
use Runalyze\Profile\Sport\SportProfile;

class SportTest extends TestCase
{
    /** @var Sport */
    protected $Sport;

    public function setUp(): void
    {
        $this->Sport = new Sport();
    }

    public function testEmptyEntity()
    {
        $this->assertEquals(PaceEnum::KILOMETER_PER_HOUR, $this->Sport->getSpeed());

        $this->assertFalse($this->Sport->hasInternalSportId());
        $this->assertInstanceOf(Generic::class, $this->Sport->getInternalSport());
        $this->assertTrue($this->Sport->getInternalSport()->isCustom());
    }

    public function testRunningAndCycling()
    {
        $this->Sport->setInternalSportId(SportProfile::RUNNING);

        $this->assertTrue($this->Sport->hasInternalSportId());
        $this->assertTrue($this->Sport->getInternalSport()->isRunning());

        $this->Sport->setInternalSportId(SportProfile::CYCLING);

        $this->assertTrue($this->Sport->hasInternalSportId());
        $this->assertTrue($this->Sport->getInternalSport()->isCycling());
    }
}
