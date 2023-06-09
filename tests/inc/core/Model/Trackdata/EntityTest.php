<?php

namespace Runalyze\Model\Trackdata;

use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase {

	protected function simpleObjectByStrings() {
		$P = new Pauses();
		$P->add(new Pause(20, 10, 140, 120));

		return new Entity(array(
			Entity::ACTIVITYID => 1,
			Entity::TIME => '20'.Entity::ARRAY_SEPARATOR.'40',
			Entity::DISTANCE => '0.1'.Entity::ARRAY_SEPARATOR.'0.2',
			Entity::HEARTRATE => '140'.Entity::ARRAY_SEPARATOR.'120',
			Entity::PAUSES => $P->asString()
		));
	}

	public function testEmptyObject() {
		$T = new Entity();

		foreach ($T->properties() as $key) {
			$this->assertFalse($T->has($key));
		}

		$this->assertEquals(0, $T->num());
		$this->assertFalse($T->hasPauses());
		$this->assertTrue($T->pauses()->isEmpty());
	}

	public function testClearing() {
		$T = $this->simpleObjectByStrings();
		$T->clear();

		foreach ($T->properties() as $key) {
			$this->assertFalse($T->has($key));
		}

		$this->assertEquals(0, $T->num());
		$this->assertFalse($T->hasPauses());
	}

	public function testCreationWithString() {
		$T = $this->simpleObjectByStrings();

		$this->assertEquals(1, $T->activityID());
		$this->assertEquals(array(20, 40), $T->time());
		$this->assertEquals(array(0.1, 0.2), $T->distance());
		$this->assertEquals(array(140, 120), $T->heartRate());

		$this->assertEquals(2, $T->num());
		$this->assertTrue($T->hasPauses());
		$this->assertEquals(1, $T->pauses()->num());
		$this->assertEquals(20, $T->pauses()->at(0)->hrDiff());

		$this->assertEquals(40, $T->totalTime());
		$this->assertEqualsWithDelta(0.2, $T->totalDistance(), 1e-6);
	}

	public function testCreatingWithArrays() {
		$T = new Entity(array(
			Entity::CADENCE => array(180, 185),
			Entity::POWER => array(200, 250),
			Entity::TEMPERATURE => array(25, 24),
			Entity::GROUNDCONTACT => array(200, 250),
			Entity::VERTICAL_OSCILLATION => array(8.0, 7.5),
			Entity::GROUNDCONTACT_BALANCE => array(6500, 6430)
		));

		$this->assertEquals(2, $T->num());
		$this->assertEquals(array(180, 185), $T->cadence());
		$this->assertEquals(array(200, 250), $T->power());
		$this->assertEquals(array( 25,  24), $T->temperature());
		$this->assertEquals(array(200, 250), $T->groundcontact());
		$this->assertEquals(array(6500, 6430), $T->groundContactBalance());
	}

	public function testWrongArraySizes() {
		$this->expectException(\RuntimeException::class);

		new Entity(array(
			Entity::TIME => array(1, 2, 3),
			Entity::DISTANCE => array(0.01, 0.03)
		));
	}

	public function testWrongArraySizeViaSet() {
		$this->expectException(\RuntimeException::class);

		$T = new Entity(array(
			Entity::TIME => array(1, 2, 3)
		));

		$T->set(Entity::DISTANCE, array(0.01, 0.03));
	}

	public function testSettingPausesDirectly() {
		$this->expectException(\InvalidArgumentException::class);

		$P = new Pauses();

		$T = new Entity();
		$T->set(Entity::PAUSES, $P->asString());
	}

	public function testDirectAccess() {
		$T = new Entity(array(
			Entity::TIME => array(1, 2, 3, 5, 10, 20)
		));

		$this->assertEquals( 1, $T->at(0, Entity::TIME));
		$this->assertEquals( 2, $T->at(1, Entity::TIME));
		$this->assertEquals( 3, $T->at(2, Entity::TIME));
		$this->assertEquals( 5, $T->at(3, Entity::TIME));
		$this->assertEquals(10, $T->at(4, Entity::TIME));
		$this->assertEquals(20, $T->at(5, Entity::TIME));
	}

	public function testInvalidAccessIndex() {
		set_error_handler(
			static function ($errno, $errstr) {
				restore_error_handler();
				throw new \Exception($errstr, $errno);
			},
			E_ALL
		);
		$this->expectException(\Exception::class);

		$T = new Entity(array(
			Entity::TIME => array(1)
		));

		$this->assertNull($T->at(2, Entity::TIME));
	}

	public function testInvalidAccessKey() {
		set_error_handler(
			static function ($errno, $errstr) {
				restore_error_handler();
				throw new \Exception($errstr, $errno);
			},
			E_ALL
		);
		$this->expectException(\Exception::class);

		$T = new Entity(array(
			Entity::TIME => array(1)
		));

		$this->assertNull($T->at(0, Entity::DISTANCE));
	}

	public function testDefectActivitiesFromHRMandGPXimport() {
		$T = new Entity(array(
			Entity::DISTANCE => array(0.05, 1.0, 1.5, 2.0),
			Entity::TIME => array(10, 20, 30, 40),
			Entity::HEARTRATE => array(120, 125, 130, 135, 140)
		));

		$this->assertEquals(4, $T->num());
	}

	public function testDefectActivitiesFromSpoQgpx() {
		$T = new Entity(array(
			Entity::DISTANCE => array(0.05, 1.0, 1.5, 2.0),
			Entity::TIME => array(10, 20, 30, 40),
			Entity::HEARTRATE => array(130, 135, 140)
		));

		$this->assertEquals(4, $T->num());
	}

	public function testEmptyArrays() {
		$T = new Entity(array(
			Entity::DISTANCE => array(0, 0, 0, 0),
			Entity::TIME => array(10, 20, 30, 40),
			Entity::HEARTRATE => array(0, 0, 0, 0),
			Entity::CADENCE => array(90, 85, 87, 89),
			Entity::TEMPERATURE => array(-2, -1, -1, 0)
		));

		$this->assertTrue($T->has(Entity::TIME));
		$this->assertTrue($T->has(Entity::CADENCE));
		$this->assertTrue($T->has(Entity::TEMPERATURE));
		$this->assertFalse($T->has(Entity::DISTANCE));
		$this->assertFalse($T->has(Entity::HEARTRATE));
	}

	public function testEmptyWithActivityID() {
		$T = new Entity(array(
			Entity::ACTIVITYID => 42
		));

		$this->assertTrue($T->isEmpty());
	}

}
