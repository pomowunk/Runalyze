<?php

namespace Runalyze\Configuration;

use Runalyze\Parameter\Integer;
use Runalyze\Parameter\Textline;

class ConfigurationCategory_MockTester extends Category {
	public function key() { return 'key'; }
	protected function createHandles() {
		$this->addHandle(new Handle('TEST', new Integer(42)));
		$this->addHandle(new Handle('SECOND', new Textline('foobar')));
	}
	public function test() {
		return $this->get('TEST');
	}
	public function Second() {
		return $this->object('SECOND');
	}
}

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2014-09-15 at 20:11:37.
 */
class ConfigurationCategoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Runalyze\Configuration\Category
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() : void {
		$this->object = new ConfigurationCategory_MockTester();
	}

	public function testAccess() {
		$this->assertEquals(42, $this->object->test());
		$this->assertEquals('foobar', $this->object->Second()->value());

		$this->object->Second()->set('test');
		$this->assertEquals('test', $this->object->Second()->value());
	}

	public function testKeys() {
		$this->assertEquals( array('TEST', 'SECOND'), $this->object->keys() );
	}

}
