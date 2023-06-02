<?php

namespace Runalyze\Tests\Metrics\Common\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Common\Unit\None;

class NoneTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new None();

        $this->assertEqualsWithDelta(1.23, $unit->fromBaseUnit(1.23), 1e-6);
        $this->assertEqualsWithDelta(3.14, $unit->toBaseUnit(3.14), 1e-6);
        $this->assertEquals('', $unit->getAppendix());
    }
}
