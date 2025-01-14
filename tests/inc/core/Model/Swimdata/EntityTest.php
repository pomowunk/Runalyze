<?php

namespace Runalyze\Model\Swimdata;

use PHPUnit\Framework\TestCase;
use Runalyze\Model\Trackdata;

class EntityTest extends TestCase {

	protected function simpleObject() {
		return new Entity(array(
			Entity::POOL_LENGTH => 2500,
			Entity::STROKE => array(25, 20, 15, 20),
			Entity::STROKETYPE => array(2, 2, 2, 2)
		));
	}

	public function testEmptyObject() {
		$T = new Entity();

		$this->assertFalse($T->has(Entity::STROKE));
		$this->assertFalse($T->has(Entity::STROKETYPE));
		$this->assertFalse($T->has(Entity::SWOLF));
		$this->assertFalse($T->has(Entity::SWOLFCYCLES));
	}

	public function testSimpleObject() {
		$T = $this->simpleObject();

		$this->assertEquals(2500, $T->poollength());
		$this->assertEquals(array(25, 20, 15, 20), $T->stroke());
		$this->assertEquals(array(2, 2, 2, 2), $T->stroketype());
		$this->assertEquals(array(), $T->swolf());
		$this->assertEquals(array(), $T->swolfcycles());
	}

	/**
	 * @see https://github.com/Runalyze/Runalyze/issues/1864
	 */
	public function testFillingDistanceArray() {
		foreach (array(29, 30, 31, 39, 40, 41) as $num) {
			$Swim = new Entity(array(
				Entity::POOL_LENGTH => 5000,
				Entity::STROKE => array_fill(0, $num, 25)
			));
			$Track = new Trackdata\Entity(array(
				Trackdata\Entity::TIME => range(0, $num - 1)
			));
			$Swim->fillDistanceArray($Track);

			$this->assertEqualsWithDelta($num * 0.05, $Track->totalDistance(), 1e-6);
		}
	}

}
