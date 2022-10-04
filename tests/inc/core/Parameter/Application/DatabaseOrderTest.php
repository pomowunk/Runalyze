<?php

namespace Runalyze\Parameter\Application;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2014-09-19 at 18:53:43.
 */
class DatabaseOrderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var \Runalyze\Parameter\Application\DatabaseOrder
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() : void {
		$this->object = new DatabaseOrder;
	}

	public function testAsQuery() {
		$this->object->set( DatabaseOrder::ASC );
		$this->assertEquals('ORDER BY `id` ASC', $this->object->asQuery());

		$this->object->set( DatabaseOrder::DESC );
		$this->assertEquals('ORDER BY `id` DESC', $this->object->asQuery());

		$this->object->set( DatabaseOrder::ALPHA );
		$this->assertEquals('ORDER BY `name` ASC', $this->object->asQuery());
	}

	public function testAscendingOrdering() {
		$array = array(
			'3' => array('id' => 3),
			'5' => array('id' => 5),
			'2' => array('id' => 2),
			'1' => array('id' => 1),
			'4' => array('id' => 4)
		);

		$this->object->set( DatabaseOrder::ASC );
		$this->object->sort($array);

		$this->assertEquals(array(
			'1' => array('id' => 1),
			'2' => array('id' => 2),
			'3' => array('id' => 3),
			'4' => array('id' => 4),
			'5' => array('id' => 5)
		), $array);
	}

	public function testDescendingOrdering() {
		$array = array(
			'3' => array('id' => 3),
			'5' => array('id' => 5),
			'2' => array('id' => 2),
			'1' => array('id' => 1),
			'4' => array('id' => 4)
		);

		$this->object->set( DatabaseOrder::DESC );
		$this->object->sort($array);

		$this->assertEquals(array(
			'5' => array('id' => 5),
			'4' => array('id' => 4),
			'3' => array('id' => 3),
			'2' => array('id' => 2),
			'1' => array('id' => 1)
		), $array);
	}

	public function testAlphabeticalOrdering() {
		$array = array(
			'f' => array('name' => 'Foo'),
			'v' => array('name' => 'Value'),
			't' => array('name' => 'Test'),
			'b' => array('name' => 'Bar'),
			'n' => array('name' => 'Name')
		);

		$this->object->set( DatabaseOrder::ALPHA );
		$this->object->sort($array);

		$this->assertEquals(array(
			'b' => array('name' => 'Bar'),
			'f' => array('name' => 'Foo'),
			'n' => array('name' => 'Name'),
			't' => array('name' => 'Test'),
			'v' => array('name' => 'Value')
		), $array);
	}

}
