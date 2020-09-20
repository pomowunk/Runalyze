<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * #TSC add new sport "Bergsteigen"
 * @codeCoverageIgnore
 */
class Mountaineering extends AbstractSport
{
    public function __construct()
    {
        parent::__construct(SportProfile::MOUNTAINEERING);
    }

    public function getIconClass()
    {
        return 'icons8-Mountaineering';
    }

    public function getName()
    {
        // #TSC: german text, so we need no translation
        return __('Bergsteigen');
    }

    public function getCaloriesPerHour()
    {
        return 540;
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
