<?php

namespace Runalyze\Parameter\Application;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2014-09-15 at 20:34:15.
 */
class ActivityPlotPrecisionTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var \Runalyze\Parameter\Application\ActivityPlotPrecision
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() : void {
		$this->object = new ActivityPlotPrecision;
	}

	public function testAssigns() {
		$this->object->set('100m');
		$this->assertTrue( $this->object->byDistance() );
		$this->assertFalse( $this->object->byPoints() );
		$this->assertEquals( 100, $this->object->distanceStep() );

		$this->object->set('200points');
		$this->assertFalse( $this->object->byDistance() );
		$this->assertTrue( $this->object->byPoints() );
		$this->assertEquals( 200, $this->object->numberOfPoints() );
	}

}
