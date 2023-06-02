<?php

namespace Runalyze\Tests\Metrics\Common;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class UnitConversionByFactorTraitTest extends TestCase
{
    public function testThatConversionWorksAsExpected()
    {
        $mock = $this->getMockForTrait(UnitConversionByFactorTrait::class);
        $mock->expects($this->any())
            ->method('getFactorFromBaseUnit')
            ->will($this->returnValue(1.23));

        /** @var UnitConversionByFactorTrait $mock */

        $this->assertEqualsWithDelta(2.46, $mock->fromBaseUnit(2.0), 1e-6);
        $this->assertEqualsWithDelta(2.0, $mock->toBaseUnit(2.46), 1e-6);
    }
}
