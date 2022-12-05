<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "Krafttraining"
 * @codeCoverageIgnore
 */
class StrengthTraining extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::STRENGTH_TRAINING);
    }

    public function getIconClass()
    {
        return 'icons8-Weightlift';
    }

    public function getName()
    {
        // #TSC: translation is available
        return __('Krafttraining');
    }

    public function getCaloriesPerHour()
    {
        return 260;
    }

    public function getAverageHeartRate()
    {
        return 105;
    }

    public function hasDistances()
    {
        return false;
    }

    public function hasPower()
    {
        return false;
    }

    public function isOutside()
    {
        return false;
    }

    public function getPaceUnitEnum()
    {
        return PaceEnum::KILOMETER_PER_HOUR;
    }

    public function usesShortDisplay()
    {
        return false;
    }
}
