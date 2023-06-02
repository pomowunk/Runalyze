<?php

namespace Runalyze\Tests\Sports\ClimbScore;

use PHPUnit\Framework\TestCase;
use Runalyze\Sports\ClimbScore\ClimbScore;

class ClimbScoreTest extends TestCase
{
    /** @var ClimbScore */
    protected $Score;

    protected function setUp(): void
    {
        $this->Score = new ClimbScore();
    }

    public function testEmptyConstructor()
    {
        $score = new ClimbScore();

        $this->assertFalse($score->isKnown());
        $this->assertNull($score->getScore());
    }

    public function testManualScore()
    {
        $this->assertEqualsWithDelta(3.1, (new ClimbScore(3.1))->getScore(), 1e-6);
        $this->assertEqualsWithDelta(4.2, (new ClimbScore())->setScore(4.2)->getScore(), 1e-6);
    }

    public function testExampleForCyclingTourInSwitzerland()
    {
        $this->Score->setScoreFromClassifiedClimbs(
            [0.6, 0.3, 7.7, 5.9, 0.6, 2.8],
            105.8,
            0.43
        );

        $this->assertEqualsWithDelta(5.9, $this->Score->getScore(), 0.05);
    }

    public function testSumOfFietsIndices()
    {
        $this->assertEqualsWithDelta(6.0, $this->Score->getSumOfScoresForClassifiedClimbs([1.0, 2.0, 3.0], 20.0), 1e-6);
        $this->assertEqualsWithDelta(3.0, $this->Score->getSumOfScoresForClassifiedClimbs([1.0, 2.0, 3.0], 80.0), 1e-6);
    }

    public function testThatVeryShortDistancesDoNotDisturbTheScore()
    {
        $this->assertEqualsWithDelta(2.7, $this->Score->getSumOfScoresForClassifiedClimbs([2.7], 0.01), 1e-6);
        $this->assertEqualsWithDelta(3.0, $this->Score->getSumOfScoresForClassifiedClimbs([3.0], 3.0), 1e-6);
        $this->assertEqualsWithDelta(4.2, $this->Score->getSumOfScoresForClassifiedClimbs([4.2], 20.0), 1e-6);
    }

    public function testScaleForSumOfScores()
    {
        $this->assertEqualsWithDelta(1.17, $this->Score->getScoreForSumOfSingleScores(0.0), 0.01);
        $this->assertEqualsWithDelta(2.0, $this->Score->getScoreForSumOfSingleScores(0.5), 1e-6);
        $this->assertEqualsWithDelta(4.0, $this->Score->getScoreForSumOfSingleScores(2.5), 1e-6);
        $this->assertEqualsWithDelta(5.17, $this->Score->getScoreForSumOfSingleScores(4.5), 0.01);
        $this->assertEqualsWithDelta(6.0, $this->Score->getScoreForSumOfSingleScores(6.5), 1e-6);
        $this->assertEqualsWithDelta(8.0, $this->Score->getScoreForSumOfSingleScores(14.5), 1e-6);
        $this->assertEqualsWithDelta(10.0, $this->Score->getScoreForSumOfSingleScores(30.5), 1e-6);
        $this->assertEqualsWithDelta(10.0, $this->Score->getScoreForSumOfSingleScores(100.0), 1e-6);
    }

    public function testCompensationFactorForFlatParts()
    {
        $this->assertEqualsWithDelta(0.0, $this->Score->getCompensationForFlatParts(2.50), 1e-6);
        $this->assertEqualsWithDelta(0.0, $this->Score->getCompensationForFlatParts(-1.23), 1e-6);
        $this->assertEqualsWithDelta(0.0, $this->Score->getCompensationForFlatParts(1.00), 1e-6);
        $this->assertEqualsWithDelta(1.0, $this->Score->getCompensationForFlatParts(0.00), 1e-6);

        $this->assertEqualsWithDelta(0.99, $this->Score->getCompensationForFlatParts(0.10), 1e-6);
        $this->assertEqualsWithDelta(0.75, $this->Score->getCompensationForFlatParts(0.50), 1e-6);
        $this->assertEqualsWithDelta(0.64, $this->Score->getCompensationForFlatParts(0.60), 1e-6);
        $this->assertEqualsWithDelta(0.36, $this->Score->getCompensationForFlatParts(0.80), 1e-6);
    }
}
