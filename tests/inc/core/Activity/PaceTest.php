<?php

namespace Runalyze\Activity;

use PHPUnit\Framework\TestCase;
use Runalyze\Parameter\Application\PaceUnit;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2014-11-24 at 18:18:19.
 */

class PaceTest extends TestCase {

	public function testDefaultConstructor() {
		$Pace = new Pace(300);

		$this->assertEquals(Pace::STANDARD, $Pace->unitEnum());
	}

	public function testKmh() {
		$Pace = new Pace(300, 1, PaceUnit::KM_PER_H);

		$this->assertEquals('12,0&nbsp;km/h', $Pace->valueWithAppendix());
	}

	public function testMeterPerSecond() {
		$Pace = new Pace(10, 0.027, PaceUnit::M_PER_S);

		$this->assertEquals('2,7&nbsp;m/s', $Pace->valueWithAppendix());
	}

	public function testMinPerKm() {
		$Pace = new Pace(312, 1, PaceUnit::MIN_PER_KM);

		$this->assertEquals('5:12/km', $Pace->valueWithAppendix());
	}

	public function testMinPer100m() {
		$Pace = new Pace(123, 0.1, PaceUnit::MIN_PER_100M);

		$this->assertEquals('2:03/100m', $Pace->valueWithAppendix());
	}

	public function testDifferentUnitsForComparison() {
		$this->expectException(\InvalidArgumentException::class);

		$Pace1 = new Pace(300, 1, PaceUnit::MIN_PER_KM);
		$Pace2 = new Pace(360, 1, PaceUnit::MIN_PER_100M);

		$Pace1->compareTo($Pace2);
	}

	public function testKmhComparison() {
		$Pace1 = new Pace(300, 1, PaceUnit::KM_PER_H);
		$Pace2 = new Pace(360, 1, PaceUnit::KM_PER_H);
		$Pace3 = new Pace(300, 1, PaceUnit::KM_PER_H);
		$Pace4 = new Pace(180, 1, PaceUnit::KM_PER_H);

		$this->assertEquals('+2,0', $Pace1->compareTo($Pace2, true));
		$this->assertEquals('+0,0', $Pace1->compareTo($Pace3, true));
		$this->assertEquals('-8,0', $Pace1->compareTo($Pace4, true));
	}

	public function testMinPerKmComparison() {
		$Pace1 = new Pace(300, 1, PaceUnit::MIN_PER_KM);
		$Pace2 = new Pace(360, 1, PaceUnit::MIN_PER_KM);
		$Pace3 = new Pace(300, 1, PaceUnit::MIN_PER_KM);
		$Pace4 = new Pace(246, 1, PaceUnit::MIN_PER_KM);

		$this->assertEquals('+1:00', $Pace1->compareTo($Pace2, true));
		$this->assertEquals('+0:00', $Pace1->compareTo($Pace3, true));
		$this->assertEquals('-0:54', $Pace1->compareTo($Pace4, true));
	}

	public function testMethodChaining() {
		$Pace = new Pace(0, 1, PaceUnit::MIN_PER_KM);

		$this->assertEquals('3:00', $Pace->setTime(180)->value());
		$this->assertEquals('6:00', $Pace->setDistance(0.5)->value());
	}

}
