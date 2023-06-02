<?php

namespace Runalyze\Tests\Metrics\Common;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Common\UnitConversionByDividendTrait;

class UnitConversionByDividendTraitTest extends TestCase
{
    public function testThatConversionWorksAsExpected()
    {
        $mock = $this->getMockForTrait(UnitConversionByDividendTrait::class);
        $mock->expects($this->any())
            ->method('getDividendFromBaseUnit')
            ->will($this->returnValue(5));

        /** @var UnitConversionByDividendTrait $mock */

        $this->assertEqualsWithDelta(2.5, $mock->fromBaseUnit(2.0), 1e-6);
        $this->assertEqualsWithDelta(2.0, $mock->toBaseUnit(2.5), 1e-6);
    }
}
