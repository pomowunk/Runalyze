<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Backup;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonImporter;
use Runalyze\Configuration;

/**
 * @group dependsOn
 * @group dependsOnOldDatabase
 */

class JsonImporterTest extends TestCase
{
    /** @var string */
    protected $Base;

	/** @var \PDOforRunalyze */
	protected $DB;

	/** @var int */
	protected $AccountID;

	protected function setUp(): void
    {
        $this->Base = __DIR__.'/../../../../testfiles/backup/';
		$this->DB = \DB::getInstance();
        $this->AccountID = 1;
		$this->truncateTables();
	}

	protected function tearDown(): void
    {
		$this->truncateTables();
	}

	private function truncateTables()
    {
		$this->DB->exec('DELETE FROM `'.PREFIX.'training`');
		$this->DB->exec('DELETE FROM `'.PREFIX.'equipment_type`');
        $this->DB->exec('DELETE FROM `'.PREFIX.'tag`');
		$this->DB->exec('DELETE FROM `'.PREFIX.'user`');
		$this->DB->exec('DELETE FROM `'.PREFIX.'dataset`');

		$this->DB->exec('DELETE FROM `'.PREFIX.'conf` WHERE `key`="TEST_CONF"');
		$this->DB->exec('DELETE FROM `'.PREFIX.'plugin` WHERE `key`="RunalyzePluginTool_TEST"');
		$this->DB->exec('DELETE FROM `'.PREFIX.'plugin_conf` WHERE `config`="test_one"');
		$this->DB->exec('DELETE FROM `'.PREFIX.'plugin_conf` WHERE `config`="test_two"');

		$this->DB->exec('DELETE FROM `'.PREFIX.'sport`');
		$this->DB->exec('DELETE FROM `'.PREFIX.'type`');
	}

	private function fillDummyTrainings()
    {
        $statement = $this->DB->prepare('INSERT INTO `'.PREFIX.'training` (`sportid`, `time`, `distance`, `accountid`, `s`) VALUES (?, ?, ?, ?, ?)');
        $statement->execute([1, time() - DAY_IN_S, 15, $this->AccountID, 2]);
        $statement->execute([1, time(), 10, $this->AccountID, 2]);

		return 2;
	}

	private function fillDummyUser()
    {
        $statement = $this->DB->prepare('INSERT INTO `'.PREFIX.'user` (`time`, `weight`, `accountid`) VALUES (?, ?, ?)');
        $statement->execute([time() - DAY_IN_S, 72, $this->AccountID]);
        $statement->execute([time(), 70, $this->AccountID]);

		return 2;
	}

	/**
	 * Test deletes
	 */
	public function testDeleteActivities()
    {
		$numTrainings = $this->fillDummyTrainings();
		$numUser = $this->fillDummyUser();

		$Importer = new JsonImporter($this->Base.'default-empty.json.gz', $this->DB, $this->AccountID, PREFIX);
        $Importer->deleteOldActivities();

		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID)->fetchColumn());
		$this->assertEquals($numUser, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'user` WHERE `accountid`='.$this->AccountID)->fetchColumn());
	}

	public function testDeleteBody()
    {
		$numTrainings = $this->fillDummyTrainings();
		$numUser = $this->fillDummyUser();

		$Importer = new JsonImporter($this->Base.'default-empty.json.gz', $this->DB, $this->AccountID, PREFIX);
        $Importer->deleteOldBodyValues();

		$this->assertEquals($numTrainings, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID)->fetchColumn());
		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'user` WHERE `accountid`='.$this->AccountID)->fetchColumn());
	}

	/**
	 * Test updates
	 */
	public function testUpdates()
    {
        $insertConf = $this->DB->prepare('INSERT INTO `'.PREFIX.'conf` (`category`, `key`, `value`, `accountid`) VALUES (?, ?, ?, ?)');
        $insertConf->execute(['test-data', 'TEST_CONF', 'false', $this->AccountID]);
        $insertPlugin = $this->DB->prepare('INSERT INTO `'.PREFIX.'plugin` (`key`, `active`, `order`, `accountid`) VALUES (?, ?, ?, ?)');
        $insertPlugin->execute(['RunalyzePluginTool_TEST', 0, 3, $this->AccountID]);
        $pluginId = $this->DB->lastInsertId();
        $insertPluginConf = $this->DB->prepare('INSERT INTO `'.PREFIX.'plugin_conf` (`pluginid`, `config`, `value`) VALUES (?, ?, ?)');
        $insertPluginConf->execute([$pluginId, 'test_one', 2]);
        $insertPluginConf->execute([$pluginId, 'test_two', 1]);
        $insertDataset = $this->DB->prepare('INSERT INTO `'.PREFIX.'dataset` (`keyid`, `active`, `style`, `position`, `accountid`) VALUES (?, ?, ?, ?, ?)');
        $insertDataset->execute([1, 1, '', 1, $this->AccountID]);

		// Act
		$Importer = new JsonImporter($this->Base.'default-update.json.gz', $this->DB, $this->AccountID, PREFIX);
        $Importer->enableOverwritingConfig();
        $Importer->enableOverwritingDataset();
        $Importer->enableOverwritingPlugins();
		$Importer->importData();

		// Assert
		$this->assertEquals('true', $this->DB->query('SELECT `value` FROM `'.PREFIX.'conf` WHERE `accountid`='.$this->AccountID.' AND `key`="TEST_CONF" LIMIT 1')->fetchColumn());
		$this->assertEquals([1, 42], $this->DB->query('SELECT `active`, `order` FROM `'.PREFIX.'plugin` WHERE `accountid`='.$this->AccountID.' AND `key`="RunalyzePluginTool_TEST" LIMIT 1')->fetch(\PDO::FETCH_NUM));
		$this->assertEquals('1', $this->DB->query('SELECT `value` FROM `'.PREFIX.'plugin_conf` WHERE `pluginid`='.$pluginId.' AND `config`="test_one" LIMIT 1')->fetchColumn());
		$this->assertEquals('2', $this->DB->query('SELECT `value` FROM `'.PREFIX.'plugin_conf` WHERE `pluginid`='.$pluginId.' AND `config`="test_two" LIMIT 1')->fetchColumn());
		$this->assertEquals([0, 'width:auto;', 42], $this->DB->query('SELECT `active`, `style`, `position` FROM `'.PREFIX.'dataset` WHERE `accountid`='.$this->AccountID.' AND `keyid`=1')->fetch(\PDO::FETCH_NUM));
	}

	/**
	 * Test inserts
	 */
	public function testInserts()
    {
        $insertSport = $this->DB->prepare('INSERT INTO `'.PREFIX.'sport` (`name`, `accountid`) VALUES (?, ?)');
        $insertSport->execute(['ID-Increaser 1', $this->AccountID]);
        $insertSport->execute(['ID-Increaser 2', $this->AccountID]);
        $insertSport->execute(['Testsport', $this->AccountID]);
        $sportId = $this->DB->lastInsertId();
        $insertType = $this->DB->prepare('INSERT INTO `'.PREFIX.'type` (`name`, `sportid`, `accountid`) VALUES (?, ?, ?)');
        $insertType->execute(['ID-Increaser 1', $sportId, $this->AccountID]);
        $insertType->execute(['ID-Increaser 2', $sportId, $this->AccountID]);
        $insertType->execute(['Testtype', $sportId, $this->AccountID]);
        $typeId = $this->DB->lastInsertId();

		// Act
		$Importer = new JsonImporter($this->Base.'default-insert.json.gz', $this->DB, $this->AccountID, PREFIX);
		$Importer->importData();

		// Check nothing changed
		$this->assertEquals($sportId, $this->DB->query('SELECT `id` FROM `'.PREFIX.'sport` WHERE `accountid`='.$this->AccountID.' AND `name`="Testsport"')->fetchColumn());
		$this->assertEquals($typeId, $this->DB->query('SELECT `id` FROM `'.PREFIX.'type` WHERE `accountid`='.$this->AccountID.' AND `name`="Testtype"')->fetchColumn());

		// Check existing/new
		$newSportId = $this->DB->query('SELECT `id` FROM `'.PREFIX.'sport` WHERE `accountid`='.$this->AccountID.' AND `name`="Newsport"')->fetchColumn();
		$newTypeId = $this->DB->query('SELECT `id` FROM `'.PREFIX.'type` WHERE `accountid`='.$this->AccountID.' AND `name`="Newtype"')->fetchColumn();
        $newTypesSportId = $this->DB->query('SELECT `sportid` FROM `'.PREFIX.'type` WHERE `accountid`='.$this->AccountID.' AND `name`="Newtype"')->fetchColumn();

		$this->assertNotEquals(0, $newSportId);
		$this->assertNotEquals(0, $newTypeId);
        $this->assertEquals($newSportId, $newTypesSportId);

		// Check inserts
		$this->assertEquals(
            [1234567890, 70, 45, 205],
            $this->DB->query('SELECT `time`, `weight`, `pulse_rest`, `pulse_max` FROM `'.PREFIX.'user` WHERE `accountid`='.$this->AccountID.' AND `time`="1234567890" LIMIT 1')->fetch(\PDO::FETCH_NUM)
        );
		$this->assertEquals(
            [1234567890, $sportId, $typeId, 900],
            $this->DB->query('SELECT `time`, `sportid`, `typeid`, `s` FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID.' AND `title`="UNITTEST-1" LIMIT 1')->fetch(\PDO::FETCH_NUM)
        );
		$this->assertEquals(
            [1234567890, $newSportId, $newTypeId, 1500],
            $this->DB->query('SELECT `time`, `sportid`, `typeid`, `s` FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID.' AND `title`="UNITTEST-RACE" LIMIT 1')->fetch(\PDO::FETCH_NUM)
        );

		$competitionId = $this->DB->query('SELECT `id` FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID.' AND `title`="UNITTEST-RACE" LIMIT 1')->fetchColumn();

		$this->assertEquals(
            [10.00, 2400],
            $this->DB->query('SELECT `official_distance`, `official_time` FROM `'.PREFIX.'raceresult` WHERE `activity_id`='.$competitionId.' LIMIT 1')->fetch(\PDO::FETCH_NUM)
        );
	}

	/**
	 * Test with equipment
	 */
	public function testWithEquipment()
    {
		$Importer = new JsonImporter($this->Base.'with-equipment.json.gz', $this->DB, $this->AccountID, PREFIX);
		$Importer->importData();

		$SportA = $this->DB->query('SELECT `id` FROM `'.PREFIX.'sport` WHERE `accountid`='.$this->AccountID.' AND `name`="Sport A"')->fetchColumn();
		$SportB = $this->DB->query('SELECT `id` FROM `'.PREFIX.'sport` WHERE `accountid`='.$this->AccountID.' AND `name`="Sport B"')->fetchColumn();

		$TypeA = $this->DB->query('SELECT `id` FROM `'.PREFIX.'equipment_type` WHERE `accountid`='.$this->AccountID.' AND `name`="Typ A"')->fetchColumn();
		$TypeAB = $this->DB->query('SELECT `id` FROM `'.PREFIX.'equipment_type` WHERE `accountid`='.$this->AccountID.' AND `name`="Typ AB"')->fetchColumn();

		$Activity1 = $this->DB->query('SELECT `id` FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID.' AND `title`="UNITTEST-1"')->fetchColumn();
		$Activity2 = $this->DB->query('SELECT `id` FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID.' AND `title`="UNITTEST-2"')->fetchColumn();
		$Activity3 = $this->DB->query('SELECT `id` FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID.' AND `title`="UNITTEST-3"')->fetchColumn();

		$EquipmentA1 = $this->DB->query('SELECT `id` FROM `'.PREFIX.'equipment` WHERE `accountid`='.$this->AccountID.' AND `name`="A1"')->fetchColumn();
		$EquipmentAB1 = $this->DB->query('SELECT `id` FROM `'.PREFIX.'equipment` WHERE `accountid`='.$this->AccountID.' AND `name`="AB1"')->fetchColumn();
		$EquipmentAB2 = $this->DB->query('SELECT `id` FROM `'.PREFIX.'equipment` WHERE `accountid`='.$this->AccountID.' AND `name`="AB2"')->fetchColumn();

		$TagA = $this->DB->query('SELECT `id` FROM `'.PREFIX.'tag` WHERE `accountid`='.$this->AccountID.' AND `tag`="TagA"')->fetchColumn();
		$TagB = $this->DB->query('SELECT `id` FROM `'.PREFIX.'tag` WHERE `accountid`='.$this->AccountID.' AND `tag`="TagB"')->fetchColumn();

		$this->assertEquals(array(
			array($SportA, $TypeA),
			array($SportA, $TypeAB),
			array($SportB, $TypeAB)
		), $this->DB->query('SELECT `sportid`, `equipment_typeid` FROM `'.PREFIX.'equipment_sport`')->fetchAll(\PDO::FETCH_NUM));

		$this->assertEquals($TypeA, $this->DB->query('SELECT `typeid` FROM `'.PREFIX.'equipment` WHERE `accountid`='.$this->AccountID.' AND `name`="A1"')->fetchColumn());
		$this->assertEquals($TypeAB, $this->DB->query('SELECT `typeid` FROM `'.PREFIX.'equipment` WHERE `accountid`='.$this->AccountID.' AND `name`="AB1"')->fetchColumn());
		$this->assertEquals($TypeAB, $this->DB->query('SELECT `typeid` FROM `'.PREFIX.'equipment` WHERE `accountid`='.$this->AccountID.' AND `name`="AB2"')->fetchColumn());

		$this->assertEquals(array($EquipmentA1), $this->DB->query('SELECT `equipmentid` FROM `'.PREFIX.'activity_equipment` WHERE `activityid`='.$Activity1)->fetchAll(\PDO::FETCH_COLUMN));
		$this->assertEquals(array($EquipmentA1, $EquipmentAB1, $EquipmentAB2), $this->DB->query('SELECT `equipmentid` FROM `'.PREFIX.'activity_equipment` WHERE `activityid`='.$Activity2)->fetchAll(\PDO::FETCH_COLUMN));
		$this->assertEquals(array($EquipmentAB1), $this->DB->query('SELECT `equipmentid` FROM `'.PREFIX.'activity_equipment` WHERE `activityid`='.$Activity3)->fetchAll(\PDO::FETCH_COLUMN));

		$this->assertEquals(array($TagA), $this->DB->query('SELECT `tagid` FROM `'.PREFIX.'activity_tag` WHERE `activityid`='.$Activity1)->fetchAll(\PDO::FETCH_COLUMN));
		$this->assertEquals(array($TagA, $TagB), $this->DB->query('SELECT `tagid` FROM `'.PREFIX.'activity_tag` WHERE `activityid`='.$Activity2)->fetchAll(\PDO::FETCH_COLUMN));
		$this->assertEquals(array($TagB), $this->DB->query('SELECT `tagid` FROM `'.PREFIX.'activity_tag` WHERE `activityid`='.$Activity3)->fetchAll(\PDO::FETCH_COLUMN));
	}

	/**
	 * Test with existing equipment and tags
	 */
	public function testWithExistingEquipmentAndTags()
    {
		$this->DB->exec('INSERT INTO `'.PREFIX.'sport` (`name`, `accountid`) VALUES("Sport A", '.$this->AccountID.')');
		$ExistingSportA = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment_type` (`name`, `accountid`) VALUES("Typ A", '.$this->AccountID.')');
		$ExistingTypeA = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment_type` (`name`, `accountid`) VALUES("Typ AB", '.$this->AccountID.')');
		$ExistingTypeAB = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment_sport` (`sportid`, `equipment_typeid`) VALUES('.$ExistingSportA.', '.$ExistingTypeA.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'tag` (`tag`, `accountid`) VALUES("Tag A", '.$this->AccountID.')');
		$ExistingTagA = $this->DB->lastInsertId();

		$Importer = new JsonImporter($this->Base.'with-equipment.json.gz', $this->DB, $this->AccountID, PREFIX);
		$Importer->importData();

		$SportA = $this->DB->query('SELECT `id` FROM `'.PREFIX.'sport` WHERE `accountid`='.$this->AccountID.' AND `name`="Sport A"')->fetchColumn();
		$SportB = $this->DB->query('SELECT `id` FROM `'.PREFIX.'sport` WHERE `accountid`='.$this->AccountID.' AND `name`="Sport B"')->fetchColumn();
		$this->assertEquals($ExistingSportA, $SportA);

		$TypeA = $this->DB->query('SELECT `id` FROM `'.PREFIX.'equipment_type` WHERE `accountid`='.$this->AccountID.' AND `name`="Typ A"')->fetchColumn();
		$TypeAB = $this->DB->query('SELECT `id` FROM `'.PREFIX.'equipment_type` WHERE `accountid`='.$this->AccountID.' AND `name`="Typ AB"')->fetchColumn();
		$this->assertEquals($ExistingTypeA, $TypeA);
		$this->assertEquals($ExistingTypeAB, $TypeAB);

		$TagA = $this->DB->query('SELECT `id` FROM `'.PREFIX.'tag` WHERE `accountid`='.$this->AccountID.' AND `tag`="Tag A"')->fetchColumn();
		$TagB = $this->DB->query('SELECT `id` FROM `'.PREFIX.'tag` WHERE `accountid`='.$this->AccountID.' AND `tag`="Tag B"')->fetchColumn();
		$this->assertEquals($ExistingTagA, $TagA);

		$this->assertEquals(array(
			array($SportA, $TypeA),
			array($SportA, $TypeAB),
			array($SportB, $TypeAB)
		), $this->DB->query('SELECT `sportid`, `equipment_typeid` FROM `'.PREFIX.'equipment_sport`')->fetchAll(\PDO::FETCH_NUM));
	}

    public function testWithExistingInternalSport()
    {
        $this->DB->exec('INSERT INTO `'.PREFIX.'sport` (`name`, `accountid`, `internal_sport_id`) VALUES("Foobar", '.$this->AccountID.', 1)');
        $foobarSportId = $this->DB->lastInsertId();

        $importer = new JsonImporter($this->Base.'with-equipment.json.gz', $this->DB, $this->AccountID, PREFIX);
        $importer->importData();

        $notInsertedSportId = $this->DB->query('SELECT `id` FROM `'.PREFIX.'sport` WHERE `accountid`='.$this->AccountID.' AND `name`="Sport A"')->fetchColumn();
        $newSportId = $this->DB->query('SELECT `id` FROM `'.PREFIX.'sport` WHERE `accountid`='.$this->AccountID.' AND `name`="Sport B"')->fetchColumn();
        $this->assertFalse($notInsertedSportId);

        $sportOfActivity1 = $this->DB->query('SELECT `sportid` FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID.' AND `title`="UNITTEST-1"')->fetchColumn();
        $sportOfActivity2 = $this->DB->query('SELECT `sportid` FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID.' AND `title`="UNITTEST-2"')->fetchColumn();
        $sportOfActivity3 = $this->DB->query('SELECT `sportid` FROM `'.PREFIX.'training` WHERE `accountid`='.$this->AccountID.' AND `title`="UNITTEST-3"')->fetchColumn();

        $this->assertEquals($foobarSportId, $sportOfActivity1);
        $this->assertEquals($foobarSportId, $sportOfActivity2);
        $this->assertEquals($newSportId, $sportOfActivity3);
    }

	/**
	 * Test deletes
	 */
	public function testDontDeleteTooMuch()
    {
		// Data of account 0
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment_type` (`accountid`,`name`) VALUES(0, "")');
		$FirstEquipmentType = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment` (`accountid`,`typeid`,`name`,`notes`) VALUES(0, '.$FirstEquipmentType.', "", "")');
		$FirstEquipment = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'route` (`accountid`) VALUES(0)');
		$FirstRoute = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'training` (`accountid`,`routeid`, `time`, `sportid`, `s`) VALUES(0, '.$FirstRoute.', 1477839906, '.Configuration::General()->runningSport().', 2)');
		$FirstTraining = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'trackdata` (`accountid`,`activityid`) VALUES(0, '.$FirstTraining.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'swimdata` (`accountid`,`activityid`) VALUES(0, '.$FirstTraining.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'hrv` (`accountid`,`activityid`) VALUES(0, '.$FirstTraining.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_equipment` (`activityid`,`equipmentid`) VALUES('.$FirstTraining.', '.$FirstEquipment.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'tag` (`accountid`,`tag`) VALUES(0, "")');
		$FirstTag = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_tag` (`activityid`,`tagid`) VALUES('.$FirstTraining.', '.$FirstTag.')');


		// Data of account 1
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment_type` (`accountid`,`name`) VALUES(1, "")');
		$SecondEquipmentType = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'equipment` (`accountid`,`typeid`,`name`,`notes`) VALUES(1, '.$SecondEquipmentType.', "", "")');
		$SecondEquipment = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'route` (`accountid`) VALUES(1)');
		$SecondRoute = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'training` (`accountid`,`routeid`, `time`, `sportid`, `s`) VALUES(1, '.$SecondRoute.', 1477839906, '.Configuration::General()->runningSport().', 2)');
		$SecondTraining = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'trackdata` (`accountid`,`activityid`) VALUES(1, '.$SecondTraining.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'swimdata` (`accountid`,`activityid`) VALUES(1, '.$SecondTraining.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'hrv` (`accountid`,`activityid`) VALUES(1, '.$SecondTraining.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_equipment` (`activityid`,`equipmentid`) VALUES('.$SecondTraining.', '.$SecondEquipment.')');
		$this->DB->exec('INSERT INTO `'.PREFIX.'tag` (`accountid`,`tag`) VALUES(1, "")');
		$SecondTag = $this->DB->lastInsertId();
		$this->DB->exec('INSERT INTO `'.PREFIX.'activity_tag` (`activityid`,`tagid`) VALUES('.$SecondTraining.', '.$SecondTag.')');

		$Importer = new JsonImporter($this->Base.'default-empty.json.gz', $this->DB, 0, PREFIX);
        $Importer->deleteOldActivities();

		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'route` WHERE `accountid`=0')->fetchColumn());
		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'training` WHERE `accountid`=0')->fetchColumn());
		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'trackdata` WHERE `accountid`=0')->fetchColumn());
		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'swimdata` WHERE `accountid`=0')->fetchColumn());
		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'hrv` WHERE `accountid`=0')->fetchColumn());
		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'activity_equipment` WHERE `equipmentid`='.$FirstEquipment)->fetchColumn());
		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'activity_tag` WHERE `tagid`='.$FirstTag)->fetchColumn());

		$this->assertEquals(1, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'route` WHERE `accountid`=1')->fetchColumn());
		$this->assertEquals(1, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'training` WHERE `accountid`=1')->fetchColumn());
		$this->assertEquals(1, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'trackdata` WHERE `accountid`=1')->fetchColumn());
		$this->assertEquals(1, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'swimdata` WHERE `accountid`=1')->fetchColumn());
		$this->assertEquals(1, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'hrv` WHERE `accountid`=1')->fetchColumn());
		$this->assertEquals(1, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'activity_equipment` WHERE `equipmentid`='.$SecondEquipment)->fetchColumn());
		$this->assertEquals(1, $this->DB->query('SELECT COUNT(*) FROM `'.PREFIX.'activity_tag` WHERE `tagid`='.$SecondTag)->fetchColumn());
	}
}
