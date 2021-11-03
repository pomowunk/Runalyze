<?php

namespace Runalyze\Tests\Metrics\Weight\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Weight\Unit\WeightEnum;

class WeightEnumTest extends TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        $this->expectNotToPerformAssertions();

        foreach (WeightEnum::getEnum() as $unit) {
            WeightEnum::get($unit);
        }
    }
}
