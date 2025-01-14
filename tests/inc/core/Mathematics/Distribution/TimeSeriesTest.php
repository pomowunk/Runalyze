<?php

namespace Runalyze\Tests\Mathematics\Distribution;

use PHPUnit\Framework\TestCase;
use Runalyze\Mathematics\Distribution\TimeSeries;

class TimeSeriesTest extends TestCase
{
    public function testSimpleArray()
    {
        $dist = new TimeSeries(
            [10, 15, 20, 15],
            [1, 3, 10, 13]
        );

        $this->assertEquals([
            10 => 1,
            15 => 5,
            20 => 7
        ], $dist->histogram());
    }
}
