<?php

namespace Runalyze\Tests\Metrics\Common\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Common\Unit\Simple;

class SimpleTest extends TestCase
{
    public function testSomeEasyValues()
    {
        $unit = new Simple('foo');

        $this->assertEqualsWithDelta(0.79, $unit->fromBaseUnit(0.79), 1e-6);
        $this->assertEquals(42, $unit->toBaseUnit(42));
        $this->assertEquals('foo', $unit->getAppendix());
    }
}
