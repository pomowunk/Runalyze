<?php

namespace Runalyze\Calculation\Power;

use PHPUnit\Framework\TestCase;
use Runalyze\Model\Trackdata;
use Runalyze\Model\Route;

use function PHPUnit\Framework\assertEqualsWithDelta;

class RunningPowerCalculatorTest extends TestCase {

	public function testEmptyExample() {
		$Calculator = new RunningPowerCalculator(
			new Trackdata\Entity(array(
			)),
			new Route\Entity(array(
			))
		);
		$Calculator->calculate();

		$this->assertEquals(0, $Calculator->average());
		$this->assertEmpty($Calculator->powerData());
	}

	/**
	 * @see https://runscribe.com/power/
	 */
	public function testRunscribeFlat() {
		$Calculator = new RunningPowerCalculator(
			new Trackdata\Entity(array(
				Trackdata\Entity::DISTANCE => [
					0.0, 1.0
				],
				Trackdata\Entity::TIME => [
					0, 5 * 60
				]
			)),
			new Route\Entity(array(
				Route\Entity::ELEVATIONS_ORIGINAL => [
					42, 42
				]
			))
		);
		$Calculator->calculate(75.0, 1.0);
		$this->assertEqualsWithDelta(array_fill(0, count($Calculator->powerData()), 319.9), $Calculator->powerData(), 1);
		$this->assertEqualsWithDelta(319.9, $Calculator->average(), 1);
	}
	
	public function testRunscribeUphill() {
		$Calculator = new RunningPowerCalculator(
			new Trackdata\Entity(array(
				Trackdata\Entity::DISTANCE => [
					0.0, 1.0
				],
				Trackdata\Entity::TIME => [
					0, 5 * 60
				]
			)),
			new Route\Entity(array(
				Route\Entity::ELEVATIONS_ORIGINAL => [
					42, 142
				]
			))
		);
		$Calculator->calculate(75.0, 1.0);
		$this->assertEqualsWithDelta(array_fill(0, count($Calculator->powerData()), 523.6), $Calculator->powerData(), 1);
		$this->assertEqualsWithDelta(523.6, $Calculator->average(), 1);
	}
	
	public function testRunscribeUphillSeries() {
		$Calculator = new RunningPowerCalculator(
			new Trackdata\Entity(array(
				Trackdata\Entity::DISTANCE => [
					0.0, .1, .2, .3, .4, .5, .6, .7, .8, .9, 1.0
				],
				Trackdata\Entity::TIME => [
					0, 30, 60, 90, 120, 150, 180, 210, 240, 270, 300
				]
			)),
			new Route\Entity(array(
				Route\Entity::ELEVATIONS_ORIGINAL => [
					42, 52, 62, 72, 82, 92, 102, 112, 122, 132, 142
				]
			))
		);
		$Calculator->calculate(75.0, 1.0);
		$this->assertEqualsWithDelta(array_fill(0, count($Calculator->powerData()), 523.6), $Calculator->powerData(), 1);
		$this->assertEqualsWithDelta(523.6, $Calculator->average(), 1);
	}
	
	public function testVaryingValues() {
		$Calculator = new RunningPowerCalculator(
			new Trackdata\Entity(array(
				Trackdata\Entity::DISTANCE => [
					0.0, 0.013, 0.028, 0.049, 0.063, 0.090, 0.129, 0.153, 0.196, 0.240, 0.293, 0.336, 0.371, 0.403, 0.426, 0.439
				],
				Trackdata\Entity::TIME => [
					10, 20, 30, 42, 50, 60, 70, 80, 90, 103, 110, 118, 128, 139, 150, 160
				]
			)),
			new Route\Entity(array(
				Route\Entity::ELEVATIONS_ORIGINAL => [
					446, 441, 437, 442, 438, 442, 447, 444, 448, 436, 425, 430, 439, 440, 439, 438
				]
			))
		);
		$Calculator->calculate(75.0, 1.0);
		$this->assertEquals(229, $Calculator->average());
		$this->assertEquals(
			[110, 108, 141, 135, 166, 174, 197, 248, 287, 289, 286, 333, 316, 283, 322, 274],
			$Calculator->powerData()
		);
	}
}
