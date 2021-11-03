<?php

namespace Runalyze\Parameter\Application;

use PHPUnit\Framework\TestCase;

class DistanceUnitSystemTest extends TestCase
{
	public function testMetricSystem()
	{
		$UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::METRIC);

		$this->assertTrue($UnitSystem->isMetric());
		$this->assertFalse($UnitSystem->isImperial());
	}

	public function testImperialSystem()
	{
		$UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL);

		$this->assertFalse($UnitSystem->isMetric());
		$this->assertTrue($UnitSystem->isImperial());
	}
}
