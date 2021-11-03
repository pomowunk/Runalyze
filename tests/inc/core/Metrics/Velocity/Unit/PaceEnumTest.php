<?php

namespace Runalyze\Tests\Metrics\Velocity\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;

class PaceEnumTest extends TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        $this->expectNotToPerformAssertions();

        foreach (PaceEnum::getEnum() as $unit) {
            PaceEnum::get($unit);
        }
    }
}
