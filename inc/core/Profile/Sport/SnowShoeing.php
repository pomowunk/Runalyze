<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "Schneeschuh"
 * @codeCoverageIgnore
 */
class SnowShoeing extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::SNOW_SHOEING);
    }

    public function getIconClass()
    {
        return 'icons8-SnowShoeing';
    }

    public function getName()
    {
        // #TSC: german text, so we need no translation
        return __('Schneeschuh');
    }

    public function getCaloriesPerHour()
    {
        return 450;
    }

    public function getAverageHeartRate()
    {
        return 120;
    }

    public function hasDistances()
    {
        return true;
    }

    public function hasPower()
    {
        return false;
    }

    public function isOutside()
    {
        return true;
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
