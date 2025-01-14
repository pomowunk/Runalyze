<?php

namespace Runalyze\Tests\Sports\EnergyExpenditure;

use PHPUnit\Framework\TestCase;
use Runalyze\Athlete;
use Runalyze\Profile\Athlete\Gender;
use Runalyze\Sports\EnergyExpenditure\HeartRateBasedEstimator;

class HeartRateBasedEstimatorTest extends TestCase
{
    /**
     * @see http://fitnowtraining.com/2012/01/formula-for-calories-burned/
     */
    public function testExampleFromFitNowTraining()
    {
        $estimator = new HeartRateBasedEstimator(
            new Athlete(
                Gender::MALE,
                null,
                null,
                70.3,
                date('Y') - 49
            )
        );

        $this->assertEqualsWithDelta(489, 60 * $estimator->getExpenditurePerMinute(148)->getValue(), 1.0);
    }
}
