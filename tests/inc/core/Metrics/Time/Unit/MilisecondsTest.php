<?php

namespace Runalyze\Tests\Metrics\Time\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Time\Unit\Miliseconds;

class MilisecondsTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Miliseconds();

        $this->assertEquals(234.0, $unit->fromBaseUnit(0.234));
        $this->assertEquals(0.75, $unit->toBaseUnit(750));
    }
}
