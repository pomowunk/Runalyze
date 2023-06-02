<?php

namespace Runalyze\Model\RaceResult;

use PHPUnit\Framework\TestCase;

/**
 * Generated by hand
 */
class EntityTest extends TestCase
{

	public function testEmptyObject()
	{
		$RaceResult = new Entity(array());

		$this->assertFalse($RaceResult->officiallyMeasured());
		$this->assertNull($RaceResult->placeTotal());
		$this->assertNull($RaceResult->placeGender());
		$this->assertNull($RaceResult->placeAgeclass());
		$this->assertNull($RaceResult->participantsTotal());
		$this->assertNull($RaceResult->participantsGender());
		$this->assertNull($RaceResult->participantsAgeclass());
	}

	public function testSimpleObject()
	{
		$RaceResult = new Entity(array(
			Entity::OFFICIAL_DISTANCE => '10.50',
			Entity::OFFICIAL_TIME => 2400,
			Entity::OFFICIALLY_MEASURED => '1',
			Entity::PLACE_TOTAL => '10',
			Entity::PLACE_GENDER => '25',
			Entity::PLACE_AGECLASS => '4',
			Entity::PARTICIPANTS_TOTAL => '1033',
			Entity::PARTICIPANTS_GENDER => '100',
			Entity::PARTICIPANTS_AGECLASS => '15',
			Entity::ACTIVITY_ID => 2
		));

		$this->assertEqualsWithDelta(10.50, $RaceResult->officialDistance(), 1e-6);
		$this->assertEquals(2400, $RaceResult->officialTime());
		$this->assertTrue($RaceResult->officiallyMeasured());
		$this->assertEquals(10, $RaceResult->placeTotal());
		$this->assertEquals(25, $RaceResult->placeGender());
		$this->assertEquals(4, $RaceResult->placeAgeclass());
		$this->assertEquals(1033, $RaceResult->participantsTotal());
		$this->assertEquals(100, $RaceResult->participantsGender());
		$this->assertEquals(15, $RaceResult->participantsAgeclass());
		$this->assertEquals(2, $RaceResult->activityId());
	}

}
