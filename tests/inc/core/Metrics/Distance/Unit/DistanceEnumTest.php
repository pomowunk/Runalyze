<?php

namespace Runalyze\Tests\Metrics\Distance\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Distance\Unit\DistanceEnum;

class DistanceEnumTest extends TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        $this->expectNotToPerformAssertions();

        foreach (DistanceEnum::getEnum() as $unit) {
            DistanceEnum::get($unit);
        }
    }
}
