<?php

namespace Runalyze\Tests\Metrics\Time\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Time\Unit\Hours;

class HoursTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Hours();

        $this->assertEqualsWithDelta(1.0, $unit->fromBaseUnit(3600), 1e-6);
        $this->assertEquals(2700, $unit->toBaseUnit(0.75));
    }
}
