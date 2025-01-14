<?php

namespace Runalyze\Data\Laps;

use PHPUnit\Framework\TestCase;
use Runalyze\Model\Route;
use Runalyze\Model\Trackdata;

class CalculatorTest extends TestCase
{

	/**
	 * @var Calculator
	 */
	protected $object;

	/**
	 * @var \Runalyze\Data\Laps\Laps
	 */
	protected $Laps;

	protected function setUp(): void
	{
		$this->Laps = new Laps;
		$this->object = new Calculator($this->Laps);
	}

	protected function trackdata()
	{
		return new Trackdata\Entity(array(
			Trackdata\Entity::TIME => array(
				0, 60, 120, 180, 240, 300,
				335, 370,
				390, 410, 430,
				464, 498,
				520, 540, 560,
				620, 680, 740, 800, 840
			), Trackdata\Entity::DISTANCE => array(
				0.0, 0.2, 0.4, 0.6, 0.8, 1.0,
				1.2, 1.4,
				1.47, 1.54, 1.60,
				1.8, 2.0,
				2.07, 2.14, 2.20,
				2.4, 2.6, 2.8, 3.0, 3.2
			), Trackdata\Entity::HEARTRATE => array(
				100, 120, 125, 130, 133, 135,
				150, 175,
				170, 165, 160,
				180, 190,
				185, 180, 175,
				170, 160, 150, 140, 130
			)
		));
	}

	protected function route()
	{
		return new Route\Entity(array(
			Route\Entity::ELEVATIONS_ORIGINAL => array(
				100, 100, 110, 120, 115, 115,
				115, 115,
				115, 115, 115,
				115, 115,
				115, 115, 115,
				115, 115, 120, 110, 100
			)
		));
	}

	protected function checkAgainst(array $ExpectedLaps)
	{
		foreach ($ExpectedLaps as $i => $Lap) {
			$this->assertEqualsWithDelta($Lap[0], $this->Laps->at($i)->duration()->seconds(), 1e-6);
			$this->assertEqualsWithDelta($Lap[1], $this->Laps->at($i)->distance()->kilometer(), 1e-6);
			$this->assertEqualsWithDelta($Lap[2], $this->Laps->at($i)->trackDuration()->seconds(), 1e-6);
			$this->assertEqualsWithDelta($Lap[3], $this->Laps->at($i)->trackDistance()->kilometer(), 1e-6);
			$this->assertEqualsWithDelta($Lap[4], $this->Laps->at($i)->HRavg()->inBPM(), 0.5);
			$this->assertEquals($Lap[5], $this->Laps->at($i)->HRmax()->inBPM());
			$this->assertEquals($Lap[6], $this->Laps->at($i)->elevationUp());
			$this->assertEquals($Lap[7], $this->Laps->at($i)->elevationDown());
		}
	}

	public function testSimpleExample()
	{
		$this->object->setDistances(Calculator::getDistancesFromString(
			'+1.0, 0.4, 0.2, 0.4, 0.2'
		));
		$this->object->calculateFrom($this->trackdata(), $this->route());

		$this->assertEquals(6, $this->Laps->num());
		$this->checkAgainst(array(
			array(300, 1.0, 300, 1.0, 129, 135, 20, 5),
			array(70, 0.4, 370, 1.4, 163, 175, 0, 0),
			array(60, 0.2, 430, 1.6, 165, 170, 0, 0),
			array(68, 0.4, 498, 2.0, 185, 190, 0, 0),
			array(62, 0.2, 560, 2.2, 180, 185, 0, 0),
			array(280, 1.0, 840, 3.2, 151, 170, 5, 20)
		));
	}

	public function testWithoutRoute()
	{
		$this->object->setDistances(Calculator::getDistancesFromString(
			'+1.0, 0.4, 0.2, 0.4, 0.2'
		));
		$this->object->calculateFrom($this->trackdata());

		$this->assertEquals(6, $this->Laps->num());
		$this->checkAgainst(array(
			array(300, 1.0, 300, 1.0, 129, 135, 0, 0),
			array(70, 0.4, 370, 1.4, 163, 175, 0, 0),
			array(60, 0.2, 430, 1.6, 165, 170, 0, 0),
			array(68, 0.4, 498, 2.0, 185, 190, 0, 0),
			array(62, 0.2, 560, 2.2, 180, 185, 0, 0),
			array(280, 1.0, 840, 3.2, 151, 170, 0, 0)
		));
	}

	public function testConsecutiveDistances()
	{
		$this->object->setDistances(array(
			1.0, 2.0, 3.0
		));
		$this->object->calculateFrom($this->trackdata());

		$this->assertEquals(4, $this->Laps->num());
		$this->assertEqualsWithDelta(1.0, $this->Laps->at(0)->distance()->kilometer(), 1e-6);
		$this->assertEqualsWithDelta(1.0, $this->Laps->at(1)->distance()->kilometer(), 1e-6);
		$this->assertEqualsWithDelta(1.0, $this->Laps->at(2)->distance()->kilometer(), 1e-6);
		$this->assertEqualsWithDelta(1.0, $this->Laps->at(0)->trackDistance()->kilometer(), 1e-6);
		$this->assertEqualsWithDelta(2.0, $this->Laps->at(1)->trackDistance()->kilometer(), 1e-6);
		$this->assertEqualsWithDelta(3.0, $this->Laps->at(2)->trackDistance()->kilometer(), 1e-6);
	}

	public function testConsecutiveTimes()
	{
		$this->object->setTimes(array(
			300, 370, 430, 498, 560, 840
		));
		$this->object->calculateFrom($this->trackdata());

		$this->assertEquals(6, $this->Laps->num());
		$this->assertEqualsWithDelta(300, $this->Laps->at(0)->duration()->seconds(), 1e-6);
		$this->assertEqualsWithDelta(70, $this->Laps->at(1)->duration()->seconds(), 1e-6);
		$this->assertEqualsWithDelta(60, $this->Laps->at(2)->duration()->seconds(), 1e-6);
		$this->assertEqualsWithDelta(68, $this->Laps->at(3)->duration()->seconds(), 1e-6);
		$this->assertEqualsWithDelta(62, $this->Laps->at(4)->duration()->seconds(), 1e-6);
		$this->assertEqualsWithDelta(280, $this->Laps->at(5)->duration()->seconds(), 1e-6);
	}

	/**
	 * @test
	 */
	public function shouldReturnAbsoluteDistances()
	{
		//given
		$distanceStr = '1,2.5,3';

		//when
		$distanceArr = Calculator::getDistancesFromString($distanceStr);

		//then
		$this->assertEqualsWithDelta($distanceArr, array(1, 2.5, 3), 1e-6);
	}

	/**
	 * @test
	 */
	public function shouldTreatDistancesAsIntervalsWithPlus()
	{
		//given
		$distanceStr = '+1,2.5,3';

		//when
		$distanceArr = Calculator::getDistancesFromString($distanceStr);

		//then
		$this->assertEqualsWithDelta($distanceArr, array(1, 3.5, 6.5), 1e-6);
	}

	/**
	 * @test
	 */
	public function shouldReturnEmptyWhenNotSorted()
	{
		//given
		$distanceStr = '1,3,2.5';

		//when
		$distanceArr = Calculator::getDistancesFromString($distanceStr);

		//then
		$this->assertEqualsWithDelta($distanceArr, array(), 1e-6);
	}

	/**
	 * @see https://github.com/Runalyze/Runalyze/issues/1308
	 */
	public function testSpareData()
	{
		$this->object->setDistances(array(
			1, 2, 3, 4, 5, 6, 7, 8, 9, 10
		));
		$this->object->calculateFrom(
			new Trackdata\Entity(array(
				Trackdata\Entity::TIME => array(
					0, 150, 300,
					450, 600,
					800,
					1200,
					2200,
					2750
				), Trackdata\Entity::DISTANCE => array(
					0.0, 0.5, 1.0,
					1.6, 2.1,
					2.8,
					4.1,
					7.3,
					8.9
				), Trackdata\Entity::HEARTRATE => array(
					150, 150, 150,
					150, 150,
					150,
					150,
					150,
					150
				)
			))
		);

		$this->assertEquals(10, $this->Laps->num());
		$this->checkAgainst(array(
			array( 300, 1.0,  300, 1.0, 150, 150, 0, 0),
			array( 300, 1.1,  600, 2.1, 150, 150, 0, 0),
			array( 600, 2.0, 1200, 4.1, 150, 150, 0, 0),
			array(   0, 0.0, 1200, 4.1,   0,   0, 0, 0),
			array(1000, 3.2, 2200, 7.3, 150, 150, 0, 0),
			array(   0, 0.0, 2200, 7.3,   0,   0, 0, 0),
			array(   0, 0.0, 2200, 7.3,   0,   0, 0, 0),
			array( 550, 1.6, 2750, 8.9, 150, 150, 0, 0),
			array(   0, 0.0, 2750, 8.9,   0,   0, 0, 0),
			array(   0, 0.0, 2750, 8.9,   0,   0, 0, 0)
		));
	}

	public function testTimesFromString()
	{
		$this->assertEqualsWithDelta(
			array(15*60, 45*60 + 30, 1*3600 + 60 + 1),
			Calculator::getTimesFromString('15:00, 45:30, 1:01:01'),
			1e-6
		);
	}

	public function testTimesFromStringWithShortNotationForMinutes()
	{
		$this->assertEqualsWithDelta(
			array(5*60, 15*60, 20*60),
			Calculator::getTimesFromString('+5\', 10\', 5\''),
			1e-6
		);
	}

}
