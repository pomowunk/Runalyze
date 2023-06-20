<?php

namespace Runalyze\Model;

use PHPUnit\Framework\TestCase;

/**
 * @group dependsOn
 * @group dependsOnOldDatabase
 */
class FactoryTest extends TestCase {

	/**
	 * @var Factory
	 */
	protected $object;

	/**
	 * @var int
	 */
	protected $accountID;

	/**
	 * @var \PDOforRunalyze
	 */
	protected $DB;

	protected function setUp(): void {
		$this->DB = \DB::getInstance();
		$this->truncateTables();
		\Cache::clean();

		$this->accountID = rand(2, 100);
		$this->DB->exec('INSERT INTO `'.PREFIX.'account` (`id`, `username`, `mail`) VALUES('.$this->accountID.', "ModelFactoryTest", "model@factory.test")');
		$this->object = new Factory($this->accountID);
		$this->object->clearCache();
	}

	protected function tearDown(): void {
		if (null !== $this->object) {
			$this->object->clearCache();
		}

		$this->truncateTables();
		\Cache::clean();
	}

	private function truncateTables() {
		$this->DB->exec('DELETE FROM `'.PREFIX.'account` WHERE `username`="ModelFactoryTest"');
		$this->DB->exec('DELETE FROM `'.PREFIX.'training`');
		$this->DB->exec('DELETE FROM `'.PREFIX.'trackdata`');
		$this->DB->exec('DELETE FROM `'.PREFIX.'swimdata`');
		$this->DB->exec('DELETE FROM `'.PREFIX.'route`');
		$this->DB->exec('DELETE FROM `'.PREFIX.'hrv`');
		$this->DB->exec('DELETE FROM `'.PREFIX.'type`');
		$this->DB->exec('DELETE FROM `'.PREFIX.'sport`');
	}

	public function testThatNothingIsThere() {
		$this->assertTrue($this->object->activity(1)->isEmpty());
		$this->assertTrue($this->object->trackdata(1)->isEmpty());
		$this->assertTrue($this->object->swimdata(1)->isEmpty());
		$this->assertTrue($this->object->route(1)->isEmpty());
		$this->assertTrue($this->object->hrv(1)->isEmpty());
		$this->assertTrue($this->object->type(1)->isEmpty());
		$this->assertTrue($this->object->sport(1)->isEmpty());
		$this->assertTrue($this->object->equipment(1)->isEmpty());
                $this->assertTrue($this->object->tag(1)->isEmpty());
	}

	public function testStaticCacheForSport() {
		$this->DB->exec('INSERT INTO `'.PREFIX.'sport` (`id`, `name`, `accountid`) VALUES(1, "Test A", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'sport` (`id`, `name`, `accountid`) VALUES(2, "Test B", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'sport` (`id`, `name`, `accountid`) VALUES(3, "Test C", '.$this->accountID.')');

		$this->assertEquals('Test A', $this->object->sport(1)->name());

		$this->DB->exec('DELETE FROM `'.PREFIX.'sport` WHERE `id`=2');
		$this->assertEquals('Test B', $this->object->sport(2)->name());

		$this->object->clearCache('sport');
		$this->assertTrue($this->object->sport(2)->isEmpty());
		$this->assertEquals('Test C', $this->object->sport(3)->name());
	}

        public function testTags() {
		$this->DB->exec('INSERT INTO `'.PREFIX.'sport` (`id`, `name`, `accountid`) VALUES(1, "Sport A", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'tag` (`id`, `tag`, `accountid`) VALUES(1, "Tag A1", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'tag` (`id`, `tag`, `accountid`) VALUES(2, "Tag A2", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'tag` (`id`, `tag`, `accountid`) VALUES(3, "Tag B", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'tag` (`id`, `tag`, `accountid`) VALUES(4, "Tag C", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'training` (`id`, `sportid`, `accountid`, `time`, `s`) VALUES(1, 1, '.$this->accountID.', 1477843525, 2)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'training` (`id`, `sportid`, `accountid`, `time`, `s`) VALUES(2, 1, '.$this->accountID.', 1477843525, 2)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'training` (`id`, `sportid`, `accountid`, `time`, `s`) VALUES(3, 1, '.$this->accountID.', 1477843525, 2)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_tag` (`activityid`, `tagid`) VALUES(1, 1)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_tag` (`activityid`, `tagid`) VALUES(1, 2)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_tag` (`activityid`, `tagid`) VALUES(2, 3)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_tag` (`activityid`, `tagid`) VALUES(3, 4)');

		$this->assertEquals(array(1, 2), $this->object->tagForActivity(1, true));
		$this->assertEquals(array(
			$this->object->tag(1), $this->object->tag(2)
		), $this->object->tagForActivity(1));

		$this->assertEquals(array(3), $this->object->tagForActivity(2, true));
		$this->assertEquals(array(
			$this->object->tag(3)
		), $this->object->tagForActivity(2));

		$this->assertEquals(array(4), $this->object->tagForActivity(3, true));
		$this->assertEquals(array(
			$this->object->tag(4)
		), $this->object->tagForActivity(3));

		$this->assertEquals(array(
			$this->object->tag(1), $this->object->tag(2), $this->object->tag(3), $this->object->tag(4)
		), $this->object->allTags());
        }

	public function testEquipment() {
		$this->DB->exec('INSERT INTO `'.PREFIX.'sport` (`id`, `name`, `accountid`) VALUES(1, "Sport A", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'sport` (`id`, `name`, `accountid`) VALUES(2, "Sport B", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'sport` (`id`, `name`, `accountid`) VALUES(3, "Sport C", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment_type` (`id`, `name`, `accountid`) VALUES(1, "Equipment Type AB", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment_type` (`id`, `name`, `accountid`) VALUES(2, "Equipment Type C", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment_sport` (`sportid`, `equipment_typeid`) VALUES(1, 1)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment_sport` (`sportid`, `equipment_typeid`) VALUES(2, 1)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment_sport` (`sportid`, `equipment_typeid`) VALUES(3, 2)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment` (`id`, `name`, `typeid`, `notes`, `accountid`) VALUES(1, "Equipment A1", 1, "", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment` (`id`, `name`, `typeid`, `notes`, `accountid`) VALUES(2, "Equipment A2", 1, "", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment` (`id`, `name`, `typeid`, `notes`, `accountid`) VALUES(3, "Equipment B", 1, "", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment` (`id`, `name`, `typeid`, `notes`, `accountid`) VALUES(4, "Equipment C", 2, "", '.$this->accountID.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'training` (`id`, `sportid`, `accountid`, `time`, `s`) VALUES(1, 1, '.$this->accountID.', 1477843525, 2)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'training` (`id`, `sportid`, `accountid`, `time`, `s`) VALUES(2, 2, '.$this->accountID.', 1477843525, 2)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'training` (`id`, `sportid`, `accountid`, `time`, `s`) VALUES(3, 3, '.$this->accountID.', 1477843525, 2)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_equipment` (`activityid`, `equipmentid`) VALUES(1, 1)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_equipment` (`activityid`, `equipmentid`) VALUES(1, 2)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_equipment` (`activityid`, `equipmentid`) VALUES(2, 3)');
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_equipment` (`activityid`, `equipmentid`) VALUES(3, 4)');

		$this->assertEquals(array(1, 2), $this->object->equipmentForActivity(1, true));
		$this->assertEquals(array(
			$this->object->equipment(1), $this->object->equipment(2)
		), $this->object->equipmentForActivity(1));

		$this->assertEquals(array(3), $this->object->equipmentForActivity(2, true));
		$this->assertEquals(array(
			$this->object->equipment(3)
		), $this->object->equipmentForActivity(2));

		$this->assertEquals(array(4), $this->object->equipmentForActivity(3, true));
		$this->assertEquals(array(
			$this->object->equipment(4)
		), $this->object->equipmentForActivity(3));

		$this->assertEquals(array(), $this->object->allTypes());

		$this->assertEquals(array(
			$this->object->sport(1), $this->object->sport(2), $this->object->sport(3)
		), $this->object->allSports());
	}

}
