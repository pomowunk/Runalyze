<?php

namespace Runalyze\Data\Weather;

use PHPUnit\Framework\TestCase;

class SourcesTest extends TestCase
{

	public function testThatStringsAreDefined()
	{
		$this->expectNotToPerformAssertions();

		foreach (Sources::getEnum() as $id) {
			Sources::stringFor($id);
		}
	}

}
