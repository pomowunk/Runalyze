<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Configuration;

use App\Entity\Sport;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Metrics\Velocity\Unit\SecondsPer500y;
use Runalyze\Metrics\Velocity\Unit\SecondsPerMile;

class UnitSystemTest extends TestCase
{
    public function testThatAllUnitsAreAccessible()
    {
        $this->expectNotToPerformAssertions();

        $unitSystem = new UnitSystem(new RunalyzeConfigurationList());

        $unitSystem->getDistanceUnit();
        $unitSystem->getEnergyUnit();
        $unitSystem->getHeartRateUnit();
        $unitSystem->getPaceUnit();
        $unitSystem->getTemperatureUnit();
        $unitSystem->getWeightUnit();
    }

    public function testThatPaceUnitCanBeSet()
    {
        $unitSystem = new UnitSystem(new RunalyzeConfigurationList());
        $unitSystem->setPaceUnit(new SecondsPer500y());

        $this->assertInstanceOf(SecondsPer500y::class, $unitSystem->getPaceUnit());
    }

    public function testThatPaceUnitCanBeSetFromSport()
    {
        $sport = new Sport();
        $sport->setSpeed(PaceEnum::SECONDS_PER_MILE);

        $unitSystem = new UnitSystem(new RunalyzeConfigurationList());
        $unitSystem->setPaceUnitFromSport($sport);

        $this->assertInstanceOf(SecondsPerMile::class, $unitSystem->getPaceUnit());
    }
}
