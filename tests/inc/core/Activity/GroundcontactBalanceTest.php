<?php

namespace Runalyze\Activity;

use PHPUnit\Framework\TestCase;

class GroundcontactBalanceTest extends TestCase
{

	public function testStaticFunction()
	{
		$this->assertEquals('49.5L/50.5R&nbsp;%', GroundcontactBalance::format(4950, true));
		$this->assertEquals('49.5L/50.5R', GroundcontactBalance::format(4950, false));
	}

	public function testSetter()
	{
		$Balance = new GroundcontactBalance(4950);

		$this->assertEqualsWithDelta(49.5, $Balance->leftInPercent(), 1e-6);
		$this->assertEqualsWithDelta(50.5, $Balance->rightInPercent(), 1e-6);

		$Balance->set(5000);
		$this->assertEqualsWithDelta(50.0, $Balance->leftInPercent(), 1e-6);

		$Balance->setPercent(50.5);
		$this->assertEqualsWithDelta(50.5, $Balance->leftInPercent(), 1e-6);
		$this->assertEquals(5050, $Balance->value());
	}

	public function testIsKnown()
	{
		$Balance = new GroundcontactBalance(0);
		$this->assertFalse($Balance->isKnown());

		$Balance->set(5050);
		$this->assertTrue($Balance->isKnown());
	}

}
