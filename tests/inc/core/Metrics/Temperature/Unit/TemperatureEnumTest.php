<?php

namespace Runalyze\Tests\Metrics\Temperature\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Temperature\Unit\TemperatureEnum;

class TemperatureEnumTest extends TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        $this->expectNotToPerformAssertions();

        foreach (TemperatureEnum::getEnum() as $unit) {
            TemperatureEnum::get($unit);
        }
    }
}
