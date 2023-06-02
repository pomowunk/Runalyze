<?php

namespace Runalyze\Tests\Metrics\Distance\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Distance\Unit\Meter;

class MeterTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Meter();

        $this->assertEquals(1000, $unit->fromBaseUnit(1.0));
        $this->assertEqualsWithDelta(1.0, $unit->toBaseUnit(1000), 1e-6);

        $this->assertEquals(3141, $unit->fromBaseUnit(3.141));
        $this->assertEqualsWithDelta(3.141, $unit->toBaseUnit(3141), 1e-6);
    }
}
