<?php

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Velocity\Unit\SecondsPer500y;

class SecondsPer500yTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new SecondsPer500y();

        $this->assertEqualsWithDelta(137, $unit->fromBaseUnit(300), 0.5);
        $this->assertEqualsWithDelta(300, $unit->toBaseUnit(137), 0.5);
    }
}
