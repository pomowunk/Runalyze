<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Form\Type\DurationNullableType;

class DurationNullableTypeTest extends TestCase
{
    /** @var DurationNullableType */
    protected $Type;

    protected function setUp(): void
    {
        $this->Type = new DurationNullableType();
    }

    public function testReverseTransform()
    {
        $this->assertNull($this->Type->reverseTransform('0:00'));
    }
}
