<?php

namespace Runalyze\Activity;

use PHPUnit\Framework\TestCase;

class DistanceTest extends TestCase
{
	public function testSimpleDistances()
	{
		$Distance = new Distance;

		$this->assertTrue($Distance->isZero());
		$this->assertFalse($Distance->isNegative());

		$this->assertEqualsWithDelta(10, $Distance->set(10)->kilometer(), 1e-6);
		$this->assertEqualsWithDelta(20, $Distance->multiply(2)->kilometer(), 1e-6);
		$this->assertEqualsWithDelta(21.1, $Distance->add(new Distance(1.1))->kilometer(), 1e-6);
		$this->assertFalse($Distance->isZero());

		$this->assertEqualsWithDelta(-0.9, $Distance->subtract(new Distance(22))->kilometer(), 1e-6);
		$this->assertTrue($Distance->isNegative());
	}

	public function testStaticFormatting()
	{
		$this->assertEquals('1,00&nbsp;km', Distance::format(1.0));
	}

	public function testAutoFormat()
	{
		$Distance = new Distance;

		$this->assertEquals('100m', $Distance->set(0.1)->stringAuto());
		$this->assertEquals('1.000m', $Distance->set(1.0)->stringAuto());
		$this->assertEquals('1.500m', $Distance->set(1.5)->stringAuto());
		$this->assertEquals('1,75&nbsp;km', $Distance->set(1.75)->stringAuto());
		$this->assertEquals('3.000m', $Distance->set(3.0)->stringAuto());
		$this->assertEquals('5,00&nbsp;km', $Distance->set(5)->stringAuto());
	}

	public function testWithoutUnit()
	{
		$Distance = new Distance;

		$this->assertEquals('100', $Distance->set(0.1)->stringMeter(false));
		$this->assertEquals('0,10', $Distance->set(0.1)->stringKilometer(false, 2));
		$this->assertEquals('1.0', $Distance->set(1.609)->stringMiles(false, 1));
	}
}
