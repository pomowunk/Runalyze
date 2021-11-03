<?php

namespace Runalyze\Tests\Metrics\Energy\Unit;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Energy\Unit\EnergyEnum;

class EnergyEnumTest extends TestCase
{
    public function testThatAllUnitsCanBeConstructed()
    {
        $this->expectNotToPerformAssertions();

        foreach (EnergyEnum::getEnum() as $unit) {
            EnergyEnum::get($unit);
        }
    }
}
