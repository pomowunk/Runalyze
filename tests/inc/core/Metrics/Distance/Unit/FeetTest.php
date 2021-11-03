<?php

namespace Runalyze\Tests\Metrics\Distance\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Distance\Unit\Feet;

class FeetTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Feet();

        $this->assertEqualsWithDelta(3.28, $unit->fromBaseUnit(0.001), 0.01);
        $this->assertEqualsWithDelta(0.001, $unit->toBaseUnit(3.28), 0.01);
    }
}
