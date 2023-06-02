<?php

namespace Runalyze\Activity;

use PHPUnit\Framework\TestCase;

class TrainingEffectTest extends TestCase
{
	public function testInvalidValueTooSmall()
	{
    	$this->expectException(\InvalidArgumentException::class);

        new TrainingEffect(0.9);
	}

    public function testInvalidValueTooBig()
    {
    	$this->expectException(\InvalidArgumentException::class);

        new TrainingEffect(5.1);
    }

    public function testInvalidValueNonNumeric()
    {
    	$this->expectException(\InvalidArgumentException::class);

        new TrainingEffect(false);
    }

    public function testInvalidValueString()
    {
    	$this->expectException(\InvalidArgumentException::class);

        new TrainingEffect('foobar');
    }

    public function testSimpleValue()
    {
        $Effect = new TrainingEffect(3.1);

        $this->assertTrue($Effect->isKnown());
        $this->assertEqualsWithDelta(3.1, $Effect->value(), 1e-6);
        $this->assertEquals(TrainingEffectLevel::IMPROVING, $Effect->level());

        $this->assertEqualsWithDelta(2.9, $Effect->set(2.9)->value(), 1e-6);
        $this->assertEquals(TrainingEffectLevel::MAINTAINING, $Effect->level());

        $this->assertFalse($Effect->set(null)->isKnown());
    }

    public function testFormattingValues()
    {
        $this->assertEquals('', TrainingEffect::format(null));
        $this->assertEquals('3.1', TrainingEffect::format(3.14));
        $this->assertEquals('5.0', TrainingEffect::format(5.0));
    }

    public function testUnknownValue()
    {
        $Effect = new TrainingEffect();

        $this->assertFalse($Effect->isKnown());
        $this->assertEquals('', $Effect->string());
        $this->assertEquals('', $Effect->shortDescription());
        $this->assertEquals('', $Effect->description());
        $this->assertEquals(0, $Effect->numericValue());
        $this->assertNull($Effect->value());
        $this->assertNull($Effect->level());
    }
}
