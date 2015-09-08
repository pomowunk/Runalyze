<?php

namespace Runalyze\Model\Activity;

use Runalyze\Configuration;
use Runalyze\Model;

use PDO;
use Shoe;

/**
 * Generated by hand
 */
class UpdaterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var \PDO
	 */
	protected $PDO;

	protected $OutdoorID;
	protected $IndoorID;
	protected $ShoeID1;
	protected $ShoeID2;

	protected function setUp() {
		\Cache::clean();
		$this->PDO = \DB::getInstance();
		$this->PDO->exec('INSERT INTO `'.PREFIX.'sport` (`name`,`kcal`,`outside`,`accountid`,`power`,`HFavg`) VALUES("",600,1,0,1,160)');
		$this->OutdoorID = $this->PDO->lastInsertId();
		$this->PDO->exec('INSERT INTO `'.PREFIX.'sport` (`name`,`kcal`,`outside`,`accountid`,`power`,`HFavg`) VALUES("",400,0,0,0,100)');
		$this->IndoorID = $this->PDO->lastInsertId();

		\SportFactory::reInitAllSports();
	}

	protected function tearDown() {
		$this->PDO->exec('DELETE FROM `'.PREFIX.'training`');
		$this->PDO->exec('DELETE FROM `'.PREFIX.'sport`');

		\Cache::clean();
	}

	/**
	 * @param array $data
	 * @return int
	 */
	protected function insert(array $data) {
		$Inserter = new Inserter($this->PDO, new Object($data));
		$Inserter->setAccountID(0);
		$Inserter->insert();

		return $Inserter->insertedID();
	}

	/**
	 * @param \Runalyze\Model\Activity\Object $new
	 * @param \Runalyze\Model\Activity\Object $old [optional]
	 * @param \Runalyze\Model\Trackdata\Object $track [optional]
	 * @param \Runalyze\Model\Route\Object $route [optional]
	 * @return \Runalyze\Model\Activity\Object
	 */
	protected function update(Object $new, Object $old = null, Model\Trackdata\Object $track = null, Model\Route\Object $route = null, $force = false) {
		$Updater = new Updater($this->PDO, $new, $old);
		$Updater->setAccountID(0);

		if (NULL !== $track) {
			$Updater->setTrackdata($track);
		}
		if (NULL !== $route) {
			$Updater->setRoute($route);
		}

		$Updater->forceRecalculations($force);
		$Updater->update();

		return $this->fetch($new->id());
	}

	/**
	 * @param int $id
	 * @return \Runalyze\Model\Activity\Object
	 */
	protected function fetch($id) {
		return new Object(
			$this->PDO->query('SELECT * FROM `'.PREFIX.'training` WHERE `id`="'.$id.'" AND `accountid`=0')->fetch(PDO::FETCH_ASSOC)
		);
	}

	/**
	 * @expectedException \PHPUnit_Framework_Error
	 */
	public function testWrongObject() {
		new Updater($this->PDO, new Model\Trackdata\Object);
	}

	public function testSimpleUpdate() {
		$OldObject = $this->fetch( $this->insert(array(
			Object::DISTANCE => 10,
			Object::TIME_IN_SECONDS => 3000
		)) );

		$NewObject = clone $OldObject;
		$NewObject->set(Object::TIME_IN_SECONDS, 3600);

		$Result = $this->update($NewObject, $OldObject);

		$this->assertEquals(10, $Result->distance());
		$this->assertEquals(3600, $Result->duration());
		$this->assertGreaterThan(time()-10, $Result->get(Object::TIMESTAMP_EDITED));
	}

	public function testWithCalculationsFromAdditionalObjects() {
		$OldObject = $this->fetch( $this->insert(array(
			Object::DISTANCE => 10,
			Object::TIME_IN_SECONDS => 3000,
			Object::HR_AVG => 150,
			Object::SPORTID => Configuration::General()->runningSport()
		)) );

		$NewObject = clone $OldObject;

		$Result = $this->update($NewObject, $OldObject, new Model\Trackdata\Object(array(
			Model\Trackdata\Object::TIME => array(1500, 3000),
			Model\Trackdata\Object::HEARTRATE => array(125, 175)
		)), new Model\Route\Object(array(
			Model\Route\Object::ELEVATION_UP => 500,
			Model\Route\Object::ELEVATION_DOWN => 100
		)), true);

		$this->assertEquals($OldObject->vdotByTime(), $Result->vdotByTime());
		$this->assertEquals($OldObject->vdotByHeartRate(), $Result->vdotByHeartRate());
		$this->assertGreaterThan($OldObject->vdotWithElevation(), $Result->vdotWithElevation());
		$this->assertGreaterThan($OldObject->jdIntensity(), $Result->jdIntensity());
		$this->assertGreaterThan($OldObject->trimp(), $Result->trimp());
	}

	public function testTrimpCalculations() {
		$OldObject = $this->fetch( $this->insert(array(
			Object::TIME_IN_SECONDS => 3000,
			Object::SPORTID => $this->IndoorID
		)) );

		$NewObject = clone $OldObject;
		$NewObject->set(Object::SPORTID, $this->OutdoorID);

		$Result = $this->update($NewObject, $OldObject);

		$this->assertGreaterThan($OldObject->trimp(), $Result->trimp());
	}

	public function testUnsettingRunningValues() {
		$OldObject = $this->fetch( $this->insert(array(
			Object::DISTANCE => 10,
			Object::TIME_IN_SECONDS => 3000,
			Object::HR_AVG => 150,
			Object::SPORTID => Configuration::General()->runningSport()
		)) );

		$this->assertGreaterThan(0, $OldObject->vdotByTime());
		$this->assertGreaterThan(0, $OldObject->vdotByHeartRate());
		$this->assertGreaterThan(0, $OldObject->vdotWithElevation());
		$this->assertGreaterThan(0, $OldObject->jdIntensity());
		$this->assertGreaterThan(0, $OldObject->trimp());

		$NewObject = clone $OldObject;
		$NewObject->set(Object::SPORTID, $NewObject->sportid() + 1);

		$Result = $this->update($NewObject, $OldObject);

		$this->assertEquals(0, $Result->vdotByTime());
		$this->assertEquals(0, $Result->vdotByHeartRate());
		$this->assertEquals(0, $Result->vdotWithElevation());
		$this->assertEquals(0, $Result->jdIntensity());
		$this->assertGreaterThan(0, $Result->trimp());
	}

	public function testVDOTstatisticsUpdate() {
		$current = time();
		$timeago = mktime(0,0,0,1,1,2000);
		$running = Configuration::General()->runningSport();
		$raceid = Configuration::General()->competitionType();

		Configuration::Data()->updateVdotShape(0);
		Configuration::Data()->updateVdotCorrector(1);

		$Object1 = $this->fetch( $this->insert(array(
			Object::TIMESTAMP => $timeago,
			Object::DISTANCE => 10,
			Object::TIME_IN_SECONDS => 30*60,
			Object::HR_AVG => 150,
			Object::SPORTID => $running,
			Object::TYPEID => $raceid + 1,
			Object::USE_VDOT => true
		)) );

		$this->assertEquals(0, Configuration::Data()->vdotShape());
		$this->assertEquals(1, Configuration::Data()->vdotFactor());

		$Object2 = clone $Object1;
		$Object2->set(Object::TIMESTAMP, $current);
		$this->update($Object2, $Object1);

		$this->assertNotEquals(0, Configuration::Data()->vdotShape());
		$this->assertEquals(1, Configuration::Data()->vdotFactor());

		$Object3 = clone $Object2;
		$Object3->set(Object::TYPEID, $raceid);
		$this->update($Object3, $Object2);

		$this->assertNotEquals(0, Configuration::Data()->vdotShape());
		$this->assertNotEquals(1, Configuration::Data()->vdotFactor());

		$Object4 = clone $Object3;
		$Object4->set(Object::TYPEID, $raceid + 1);
		$this->update($Object4, $Object3);

		$this->assertNotEquals(0, Configuration::Data()->vdotShape());
		$this->assertEquals(1, Configuration::Data()->vdotFactor());

		$Object5 = clone $Object4;
		$Object5->set(Object::TIMESTAMP, $timeago);
		$this->update($Object5, $Object4);

		$this->assertEquals(0, Configuration::Data()->vdotShape());
		$this->assertEquals(1, Configuration::Data()->vdotFactor());
	}

	public function testStartTimeUpdate() {
		$current = time();
		$timeago = mktime(0,0,0,1,1,2000);

		Configuration::Data()->updateStartTime($current);

		$OldObject = $this->fetch( $this->insert(array(
			Object::TIMESTAMP => $current
		)) );

		$NewObject = clone $OldObject;
		$NewObject->set(Object::TIMESTAMP, $current);
		$this->update($NewObject, $OldObject);

		$this->assertEquals($current, Configuration::Data()->startTime());

		$NewObject->set(Object::TIMESTAMP, $timeago);
		$this->update($NewObject, $OldObject);

		$this->assertEquals($timeago, Configuration::Data()->startTime());

		$this->insert(array(
			Object::TIMESTAMP => $timeago + 100
		));
		$this->assertEquals($timeago, Configuration::Data()->startTime());

		$NewestObject = clone $NewObject;
		$NewestObject->set(Object::TIMESTAMP, $current);
		$this->update($NewestObject, $NewObject);

		$this->assertEquals($timeago + 100, Configuration::Data()->startTime());
	}

	public function testUpdateTemperature() {
		$OldObject = $this->fetch( $this->insert(array(
			Object::TEMPERATURE => 5
		)) );

		$this->assertFalse($OldObject->weather()->temperature()->isUnknown());

		$NewObject = clone $OldObject;
		$NewObject->weather()->temperature()->setTemperature(NULL);
		$Result = $this->update($NewObject, $OldObject);

		$this->assertTrue($Result->weather()->temperature()->isUnknown());
	}

	public function testUpdateTemperatureFromNullToZero() {
		$OldObject = $this->fetch( $this->insert(array(
			Object::TEMPERATURE => NULL
		)) );

		$this->assertTrue($OldObject->weather()->temperature()->isUnknown());

		$NewObject = clone $OldObject;
		$NewObject->weather()->temperature()->setTemperature(0);
		$Result = $this->update($NewObject, $OldObject);

		$this->assertFalse($Result->weather()->temperature()->isUnknown());
	}

	public function testUpdateTemperatureWithoutOldObject() {
		$OldObject = $this->fetch( $this->insert(array(
			Object::TEMPERATURE => 5
		)) );

		$this->assertFalse($OldObject->weather()->temperature()->isUnknown());

		$NewObject = clone $OldObject;
		$NewObject->weather()->temperature()->setTemperature(NULL);
		$Result = $this->update($NewObject);

		$this->assertTrue($Result->weather()->temperature()->isUnknown());
	}

	public function testUpdatePowerCalculation() {
		// TODO: Needs configuration setting
		if (Configuration::ActivityForm()->computePower()) {
			$OldObject = $this->fetch( $this->insert(array(
				Object::DISTANCE => 10,
				Object::TIME_IN_SECONDS => 3000,
				Object::SPORTID => $this->IndoorID
			)));

			$NewObject = clone $OldObject;
			$NewObject->set(Object::SPORTID, $this->OutdoorID);

			$Result = $this->update($NewObject, $OldObject, new Model\Trackdata\Object(array(
				Model\Trackdata\Object::TIME => array(1500, 3000),
				Model\Trackdata\Object::DISTANCE => array(5, 10)
			)));

			$this->assertEquals(0, $OldObject->power());
			$this->assertNotEquals(0, $NewObject->power());
			$this->assertNotEquals(0, $Result->power());
		}
	}

}
