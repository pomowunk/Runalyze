<?php

namespace Runalyze\Model\Route;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2014-11-10 at 18:38:24.
 */
class LoopTest extends \PHPUnit\Framework\TestCase {

	public function testRouteLoop() {
		$Loop = new Loop(new Entity(array(
			Entity::GEOHASHES => array('u1xjhxf507s1', 'u1xjhxf6b7s9', 'u1xjhxfd8jyw', 'u1xjhxf6b7s9', 'u1xjhxffrhw4')
		)));

		$Loop->nextStep();
		$this->assertEquals('u1xjhxf6b7s9', $Loop->geohash());
		$this->assertEqualsWithDelta(0.02279247104384903, $Loop->calculatedStepDistance(), 0.2);

		$Loop->setStepSize(2);
		$Loop->nextStep();
		$this->assertEquals('u1xjhxf6b7s9', $Loop->geohash());
		$this->assertEquals(0.0, $Loop->calculatedStepDistance());

		$Loop->nextStep();
		$this->assertTrue($Loop->isAtEnd());
	}

}
