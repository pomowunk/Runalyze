<?php

namespace Runalyze\Tests\Mathematics\Scale;

use PHPUnit\Framework\TestCase;
use Runalyze\Mathematics\Scale\Percental;

class PercentalTest extends TestCase
{
    /** @var Percental */
    protected $Scale;

    protected function setUp(): void
    {
        $this->Scale = new Percental();
    }

    public function testNoTransformation()
    {
        $this->assertEquals(26, $this->Scale->transform(26));
    }

    public function testMinMax()
    {
        $this->assertEquals(0, $this->Scale->transform(-10));
        $this->assertEquals(0, $this->Scale->transform(0));
        $this->assertEquals(100, $this->Scale->transform(100));
        $this->assertEquals(100, $this->Scale->transform(120));
    }

    public function testNewMinimum()
    {
        $this->Scale->setMinimum(-400);

        $this->assertEquals(40, $this->Scale->transform(-200));
        $this->assertEquals(80, $this->Scale->transform(0));
    }

    public function testNewMaximum()
    {
        $this->Scale->setMaximum(200);

        $this->assertEquals(50, $this->Scale->transform(100));
        $this->assertEquals(75, $this->Scale->transform(150));
        $this->assertEquals(100, $this->Scale->transform(210));
    }

    public function testNewScale()
    {
        $this->Scale->setMinimum(1);
        $this->Scale->setMaximum(10);

        $this->assertEqualsWithDelta(0, $this->Scale->transform(0.9), 1e-6);
        $this->assertEqualsWithDelta(5, $this->Scale->transform(1.45), 1e-6);
        $this->assertEqualsWithDelta(50, $this->Scale->transform(5.5), 1e-6);
        $this->assertEqualsWithDelta(69, $this->Scale->transform(7.2), 0.2);
        $this->assertEqualsWithDelta(89, $this->Scale->transform(9.0), 0.2);
    }
}
