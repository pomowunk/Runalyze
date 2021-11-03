<?php

use PHPUnit\Framework\TestCase;
use Runalyze\Data\Weather\HeatIndexEffect;

class HeatIndexEffectTest extends TestCase
{
	public function testThatDescriptionCanBeCalledForAllLevels()
	{
        $this->expectNotToPerformAssertions();

		foreach (HeatIndexEffect::getEnum() as $level) {
			HeatIndexEffect::description($level);
		}
	}

	public function testThatLabelCanBeCalledForAllLevels()
	{
        $this->expectNotToPerformAssertions();

		foreach (HeatIndexEffect::getEnum() as $level) {
			HeatIndexEffect::label($level);
		}
	}

	public function testThatIconCanBeCalledForAllLevels()
	{
        $this->expectNotToPerformAssertions();

		foreach (HeatIndexEffect::getEnum() as $level) {
			HeatIndexEffect::icon($level);
		}
	}

	public function testSomeLevels()
	{
		$this->assertEquals(HeatIndexEffect::NO_EFFECT, HeatIndexEffect::levelFor(79));
		$this->assertEquals(HeatIndexEffect::CAUTION, HeatIndexEffect::levelFor(90));
		$this->assertEquals(HeatIndexEffect::EXTREME_CAUTION, HeatIndexEffect::levelFor(91));
		$this->assertEquals(HeatIndexEffect::DANGER, HeatIndexEffect::levelFor(105));
		$this->assertEquals(HeatIndexEffect::EXTREME_DANGER, HeatIndexEffect::levelFor(140));
	}
}
