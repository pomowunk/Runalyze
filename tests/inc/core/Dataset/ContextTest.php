<?php

namespace Runalyze\Dataset;

use PHPUnit\Framework\TestCase;
use Runalyze\Model\Activity;

class ContextTest extends TestCase
{

	public function testThatAllStringMethodsWork()
	{
		$this->expectNotToPerformAssertions();

		$Context = new Context(array(
			Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015),
			Activity\Entity::TIME_IN_SECONDS => 3600,
			Activity\Entity::DISTANCE => 12.3,
			Keys\Pace::DURATION_SUM_WITH_DISTANCE_KEY => 42
		), 0);

		$Configuration = new DefaultConfiguration();

		foreach ($Configuration->allKeys() as $keyid) {
			Keys::get($keyid)->stringFor($Context);
		}
	}

	public function testContextFromArrayWithoutSportAndType()
	{
		$Context = new Context(array(
			Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015),
			Activity\Entity::TIME_IN_SECONDS => 3600,
			Activity\Entity::DISTANCE => 12.3,
			'more-data' => 42
		), 0);

		$this->assertEquals('30.10.2015', date('d.m.Y', $Context->activity()->timestamp()));
		$this->assertEquals(3600, $Context->activity()->duration());
		$this->assertEqualsWithDelta(12.3, $Context->activity()->distance(), 1e-6);
		$this->assertEquals(42, $Context->data('more-data'));
		$this->assertFalse($Context->hasSport());
		$this->assertFalse($Context->hasType());
		$this->assertTrue($Context->dataview() instanceof \Runalyze\View\Activity\Dataview);
		$this->assertTrue($Context->linker() instanceof \Runalyze\View\Activity\Linker);
	}

	public function testContextFromObject()
	{
		$Context = new Context(new Activity\Entity(array(
			Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015)
				)), 0);

		$this->assertEquals('30.10.2015', date('d.m.Y', $Context->activity()->timestamp()));
	}

	public function testUnknownData()
	{
		$this->expectException(\InvalidArgumentException::class);

		$Context = new Context(new Activity\Entity(array(
			Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015)
				)), 0);

		$Context->data('non-existing-key');
	}

	public function testUnknownDataWithNull()
	{
		$Context = new Context(new Activity\Entity(array(
			Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015),
			'null-data' => null
		)), 0);

		$this->assertNull($Context->data('null-data', true));
	}

	public function testWrongConstructor()
	{
		$this->expectException(\InvalidArgumentException::class);

		new Context(0, 0);
	}

	public function testWrongSetActivityData()
	{
		$this->expectException(\InvalidArgumentException::class);

		new Context(array(), 0);
	}

	public function testSettingNewActivityAfterConstructor()
	{
		$Context = new Context(new Activity\Entity(array(
			Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015)
				)), 0);
		$Context->setActivityData(array(
			Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 11, 11, 2015)
		));

		$this->assertEquals('11.11.2015', date('d.m.Y', $Context->activity()->timestamp()));
	}
}
