<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Form\Type\DistanceType;
use Runalyze\Metrics\Distance\Unit\Kilometer;

class DistanceTypeTest extends TestCase
{
    /** @var DistanceType */
    protected $Type;

    protected function setUp(): void
    {
        $this->Type = new DistanceType(new Kilometer());
    }

    public function testTransform()
    {
        $this->assertEquals('', $this->Type->transform(null));
        $this->assertEquals('1.234', $this->Type->transform(1.234));
        $this->assertEquals('1234.50', $this->Type->transform(1234.5));
        $this->assertEquals('7.69', $this->Type->transform('7.69'));
    }

    public function testReverseTransform()
    {
        $this->assertEquals(null, $this->Type->reverseTransform(null));
        $this->assertEqualsWithDelta(1.23, $this->Type->reverseTransform('1.23'), 1e-6);
        $this->assertEqualsWithDelta(1.234, $this->Type->reverseTransform('1.234'), 1e-6);
        $this->assertEqualsWithDelta(7.69, $this->Type->reverseTransform('7,69'), 1e-6);
    }
}
