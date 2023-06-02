<?php

namespace Runalyze\Tests\Metrics\Time\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Time\Unit\Miliseconds;

class MilisecondsTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Miliseconds();

        $this->assertEqualsWithDelta(234.0, $unit->fromBaseUnit(0.234), 1e-6);
        $this->assertEqualsWithDelta(0.75, $unit->toBaseUnit(750), 1e-6);
    }
}
