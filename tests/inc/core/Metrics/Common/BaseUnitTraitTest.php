<?php

namespace Runalyze\Tests\Metrics\Common;

use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Common\BaseUnitTrait;

class BaseUnitTraitTest extends TestCase
{
    public function testThatConversionWorksAsExpected()
    {
        /** @var BaseUnitTrait $mock */
        $mock = $this->getMockForTrait(BaseUnitTrait::class);

        $this->assertEqualsWithDelta(3.14, $mock->fromBaseUnit(3.14), 1e-6);
        $this->assertEqualsWithDelta(42.195, $mock->toBaseUnit(42.195), 1e-6);
    }
}
