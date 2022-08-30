<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "Bouldern"
 * @codeCoverageIgnore
 */
class Bouldering extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::BOULDERING);
    }

    public function getIconClass()
    {
        return 'icons8-Bouldern';
    }

    public function getName()
    {
        // #TSC: german text, so we need no translation
        return __('Bouldering');
    }

    public function getCaloriesPerHour()
    {
        return 500;
    }

    public function getAverageHeartRate()
    {
        return 120;
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
        return PaceEnum::SECONDS_PER_KILOMETER;
    }

    public function usesShortDisplay()
    {
        return false;
    }
}
