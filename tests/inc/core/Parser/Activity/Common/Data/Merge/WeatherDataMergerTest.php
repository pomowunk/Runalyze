<?php

namespace Runalyze\Tests\Parser\Activity\Data\Merge;

use PHPUnit\Framework\TestCase;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Parser\Activity\Common\Data\Merge\WeatherDataMerger;

class WeatherDataMergerTest extends TestCase
{
    /** @var WeatherData */
    protected $FirstData;

    /** @var WeatherData */
    protected $SecondData;

    public function setUp(): void
    {
        $this->FirstData = new WeatherData();
        $this->SecondData = new WeatherData();
    }

    public function testThatMergeWorksWithEmptyObjects()
    {
        $this->expectNotToPerformAssertions();

        (new WeatherDataMerger($this->FirstData, $this->SecondData))->merge();
    }

    public function testMergingWithSomeData()
    {
        $this->FirstData->Temperature = 0;
        $this->SecondData->Temperature = 3;
        $this->SecondData->Condition = 'sunny';

        (new WeatherDataMerger($this->FirstData, $this->SecondData))->merge();

        $this->assertEquals(0, $this->FirstData->Temperature);
        $this->assertEquals('sunny', $this->FirstData->Condition);
    }
}
