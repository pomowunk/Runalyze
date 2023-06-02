<?php

namespace Runalyze\Tests\Metrics\Common\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Common\Unit\Linear;

class LinearTest extends TestCase
{
    public function testSimpleFunction()
    {
        $unit = new Linear(
            function ($value) { return 2.0 * $value + 5.0; },
            function ($value) { return ($value - 5.0) / 2.0; },
            'foo', 1
        );

        $this->assertEqualsWithDelta(7.0, $unit->fromBaseUnit(1.0), 1e-6);
        $this->assertEqualsWithDelta(1.0, $unit->toBaseUnit(7.0), 1e-6);
        $this->assertEquals('foo', $unit->getAppendix());
    }
}
