<?php

namespace Runalyze\Data\Cadence;

use PHPUnit\Framework\TestCase;

class GeneralTest extends TestCase {

	public function testValue() {
		$Cadence = new General(90);

		$this->assertEquals(90, $Cadence->value());
	}

}
