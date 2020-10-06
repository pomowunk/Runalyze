<?php

namespace Runalyze\Profile\Weather\Mapping;

use Runalyze\Profile\Mapping\ToInternalMappingInterface;
use Runalyze\Profile\Weather\WeatherConditionProfile;

class MeteostatNetMapping implements ToInternalMappingInterface
{
    /**
     * @see https://dev.meteostat.net/getting-started/formats-and-units
     *
     * @param int|string $value
     * @return int|string
     */
    public function toInternal($value)
    {
        switch ($value) {
            case 1:
                return WeatherConditionProfile::SUNNY;
            case 2:
                return WeatherConditionProfile::FAIR; // heiter
            case 3:
                return WeatherConditionProfile::CLOUDY;
            case 4:
                return WeatherConditionProfile::CHANGEABLE;
            case 7:
            case 8:
            case 10:
            case 17:
            case 19:
                return WeatherConditionProfile::RAINY;
            case 12:
            case 13:
            case 14:
            case 15:
            case 16:
            case 20:
            case 21:
            case 22:
            case 24:
                return WeatherConditionProfile::SNOWING;
            case 9:
            case 11:
            case 18:
                return WeatherConditionProfile::HEAVYRAIN;
            case 5:
            case 6:
                return WeatherConditionProfile::FOGGY;
            case 23:
            case 25:
            case 26:
                return WeatherConditionProfile::THUNDERSTORM;
            case 27:
                return WeatherConditionProfile::WINDY;
            default:
                return WeatherConditionProfile::UNKNOWN;
        }
    }
}
