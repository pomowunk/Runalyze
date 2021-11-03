<?php

namespace Runalyze\Data\Weather;

use PHPUnit\Framework\TestCase;

class WindDegreeTest extends TestCase
{
    public function testNonNumericValue()
    {
    	$this->expectException(\InvalidArgumentException::class);

        new WindDegree('foobar');
    }

    public function testNull()
    {
        $Degrees = new WindDegree();

        $this->assertEquals(null, $Degrees->value());
        $this->assertEquals('', $Degrees->string());
        $this->assertTrue($Degrees->isUnknown());
    }

    public function testValue()
    {
        $Degrees = new WindDegree(120);

        $this->assertEquals(120, $Degrees->value());
        $this->assertEquals(180, $Degrees->set(180)->value());
        $this->assertEquals('180', $Degrees->string(false));
    }
}
