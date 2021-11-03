<?php

namespace Runalyze\Tests\Metrics\HeartRate\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\HeartRate\Unit\HeartRateEnum;

class HeartRateEnumTest extends TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        $this->expectNotToPerformAssertions();

        foreach (HeartRateEnum::getEnum() as $unit) {
            HeartRateEnum::get($unit, 200, 60);
        }
    }

    public function testThatUnknownUnitCantBeConstructed()
    {
    	$this->expectException(\InvalidArgumentException::class);

        HeartRateEnum::get(42, 200, 60);
    }
}
