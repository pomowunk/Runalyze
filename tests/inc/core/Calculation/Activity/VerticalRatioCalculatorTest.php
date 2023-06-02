<?php

namespace Runalyze\Calculation\Activity;

use PHPUnit\Framework\TestCase;
use Runalyze\Model\Activity;
use Runalyze\Model\Trackdata;

class VerticalRatioCalculatorTest extends TestCase
{

	public function testForActivity()
	{
		$Calculator = VerticalRatioCalculator::forActivity(new Activity\Entity(array(
			Activity\Entity::VERTICAL_OSCILLATION => 53,
			Activity\Entity::STRIDE_LENGTH => 100
		)));

		$this->assertEqualsWithDelta(53.0, $Calculator, 1e-6);
	}

	public function testSingleValue()
	{
		$Calculator = new VerticalRatioCalculator(
			new Trackdata\Entity(array(
				Trackdata\Entity::VERTICAL_OSCILLATION => array(100),
				Trackdata\Entity::STRIDE_LENGTH => array(100)
			))
		);
		$Calculator->calculate();

		$this->assertEquals(100, $Calculator->average());
	}

	public function testSimpleArray()
	{
		$Calculator = new VerticalRatioCalculator(
			new Trackdata\Entity(array(
				Trackdata\Entity::VERTICAL_OSCILLATION => array(73, 80, 90, 100, 120),
				Trackdata\Entity::STRIDE_LENGTH => array(100, 120, 150, 150, 150)
			))
		);
		$Calculator->calculate();

		$this->assertEqualsWithDelta(69.0, $Calculator->average(), 1e-6);
		$this->assertEqualsWithDelta(
			array(73, 67, 60, 67, 80),
			$Calculator->verticalRatioData(),
			1e-6
		);
	}

}
