<?php

namespace Runalyze\Data\Laps;

use Runalyze\Model\Activity\Splits;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2015-01-09 at 14:44:06.
 */
class SplitsReaderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var SplitsReader
	 */
	protected $object;

	/**
	 * @var \Runalyze\Data\Laps\Laps
	 */
	protected $Laps;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() : void {
		$this->Laps = new Laps;
		$this->object = new SplitsReader($this->Laps);
	}

	protected function checkAgainst(array $ExpectedLaps) {
		foreach ($ExpectedLaps as $i => $Lap) {
			$this->assertEquals($Lap[0], $this->Laps->at($i)->duration()->seconds());
			$this->assertEquals($Lap[1], $this->Laps->at($i)->distance()->kilometer());
			$this->assertEquals($Lap[2], $this->Laps->at($i)->isActive());
		}
	}

	public function testSimpleExample() {
		$Splits = new Splits\Entity();
		$Splits->add(new Splits\Split(3.0, 1000, false));
		$Splits->add(new Splits\Split(0.4,   72, true));
		$Splits->add(new Splits\Split(0.2,   62, false));
		$Splits->add(new Splits\Split(0.4,   69, true));
		$Splits->add(new Splits\Split(0.2,   63, false));
		$Splits->add(new Splits\Split(2.0,  600, false));

		$this->object->readFrom($Splits);

		$this->assertEquals(6, $this->Laps->num());
		$this->checkAgainst(array(
			array(1000, 3.0, false),
			array(  72, 0.4,  true),
			array(  62, 0.2, false),
			array(  69, 0.4,  true),
			array(  63, 0.2, false),
			array( 600, 2.0, false)
		));
	}

}
