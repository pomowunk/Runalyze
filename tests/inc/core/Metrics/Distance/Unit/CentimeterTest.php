<?php

namespace Runalyze\Tests\Metrics\Distance\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Distance\Unit\Centimeter;

class CentimeterTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Centimeter();

        $this->assertEquals(100, $unit->fromBaseUnit(0.001));
        $this->assertEqualsWithDelta(0.001, $unit->toBaseUnit(100), 1e-6);
    }
}
