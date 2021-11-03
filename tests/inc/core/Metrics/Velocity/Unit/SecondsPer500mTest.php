<?php

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Velocity\Unit\SecondsPer500m;

class SecondsPer500mTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new SecondsPer500m();

        $this->assertEquals(150, $unit->fromBaseUnit(300));
        $this->assertEquals(300, $unit->toBaseUnit(150));
    }
}
