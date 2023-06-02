<?php

namespace Runalyze\Activity;

use PHPUnit\Framework\TestCase;

class VerticalRatioTest extends TestCase
{

	public function testStaticFunction()
	{
		$this->assertEquals('7.9&nbsp;%', VerticalRatio::format(79, true));
		$this->assertEquals('6.8', VerticalRatio::format(68, false));
	}

	public function testSetter()
	{
		$Ratio = new VerticalRatio(49);

		$this->assertEquals(49, $Ratio->value());
		$this->assertEqualsWithDelta(4.9, $Ratio->inPercent(), 1e-6);

		$Ratio->set(50);
		$this->assertEqualsWithDelta(5.0, $Ratio->inPercent(), 1e-6);

		$Ratio->setPercent(5.5);
		$this->assertEqualsWithDelta(5.5, $Ratio->inPercent(), 1e-6);
		$this->assertEquals(55, $Ratio->value());
	}

}
