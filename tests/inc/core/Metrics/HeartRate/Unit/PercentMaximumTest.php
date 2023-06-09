<?php

namespace Runalyze\Tests\Metrics\HeartRate\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\HeartRate\Unit\PercentMaximum;

class PercentMaximumTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new PercentMaximum(200);

        $this->assertEquals(200, $unit->getMaximalHeartRate());

        $this->assertEqualsWithDelta(0.5, $unit->fromBaseUnit(100), 1e-6);
        $this->assertEquals(100, $unit->toBaseUnit(0.5));

        $this->assertEqualsWithDelta(0.75, $unit->fromBaseUnit(150), 1e-6);
        $this->assertEquals(150, $unit->toBaseUnit(0.75));
    }

    public function testWithOtherMaximalHeartRate()
    {
        $unit = new PercentMaximum(180);

        $this->assertEquals(180, $unit->getMaximalHeartRate());

        $this->assertEqualsWithDelta(0.67, $unit->fromBaseUnit(120), 0.01);
        $this->assertEqualsWithDelta(120.6, $unit->toBaseUnit(0.67), 0.01);

        $this->assertEqualsWithDelta(0.75, $unit->fromBaseUnit(135), 1e-6);
        $this->assertEquals(135, $unit->toBaseUnit(0.75));
    }
}
