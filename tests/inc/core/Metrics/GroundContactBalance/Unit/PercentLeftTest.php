<?php

namespace Runalyze\Tests\Metrics\GroundContactBalance\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\GroundContactBalance\Unit\PercentLeft;

class PercentLeftTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new PercentLeft();

        $this->assertEqualsWithDelta(49.5, $unit->fromBaseUnit(4950), 1e-6);
        $this->assertEquals(5120, $unit->toBaseUnit(51.2));
    }
}
