<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Bridge\Activity\Calculation;

use App\Entity\Sport;
use App\Entity\Training;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\TrimpCalculator;
use Runalyze\Profile\Athlete\Gender;

class TrimpCalculatorTest extends TestCase
{
    /** @var TrimpCalculator */
    protected $Calculator;

    /** @var Training */
    protected $Activity;

    protected function setUp(): void
    {
        $this->Activity = new Training();
        $this->Activity->setSport(new Sport());
        $this->Calculator = new TrimpCalculator(10000, 400);
    }

    public function testEmptyActivity()
    {
        $this->Calculator->calculateFor($this->Activity, Gender::MALE, 200, 60);

        $this->assertEquals(0, $this->Activity->getTrimp());
    }

    public function testInvalidActivity()
    {
        $this->Activity->setS(1);
        $this->Activity->setPulseAvg(400);

        $this->Calculator->calculateFor($this->Activity, Gender::MALE, 200, 60);

        $this->assertNull($this->Activity->getTrimp());
    }
}
