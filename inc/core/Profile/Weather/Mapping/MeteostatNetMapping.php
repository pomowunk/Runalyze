<?php

namespace Runalyze\Profile\Weather\Mapping;

use Runalyze\Profile\Mapping\ToInternalMappingInterface;
use Runalyze\Profile\Weather\WeatherConditionProfile;

class MeteostatNetMapping implements ToInternalMappingInterface
{
    /**
     * @see https://dev.meteostat.net/formats.html#weather-condition-codes
     *
     * @param int|string $value
     * @return int|string
     */
    public function toInternal($value)
    {
        switch ($value) {
            case 1:  // clear
                return WeatherConditionProfile::SUNNY;
            case 2:  // fair
                return WeatherConditionProfile::FAIR; // heiter
            case 3:  // cloudy
                return WeatherConditionProfile::CLOUDY;
            case 4:  // overcast
                return WeatherConditionProfile::CHANGEABLE;
            case 7:  // light rain
            case 8:  // rain
            case 10: // freezing rain
            case 17: // rain shower
            case 19: // sleet shower
                return WeatherConditionProfile::RAINY;
            case 12: // sleet / schneeregen
            case 13: // heavy sleet
            case 14: // light snowfall
            case 15: // snowfall
            case 16: // heavy snowfall
            case 20: // heavy sleet shower
            case 21: // snow shower
            case 22: // heavy snow shower
            case 24: // hail 
                return WeatherConditionProfile::SNOWING;
            case 9:  // heavy rain
            case 11: // heavy freezing rain
            case 18: // heavy rain shower
                return WeatherConditionProfile::HEAVYRAIN;
            case 5:  // frog
            case 6:  // freezing frog
                return WeatherConditionProfile::FOGGY;
            case 23: // lightning
            case 25: // thunderstorm
            case 26: // heavy thunderstrom
                return WeatherConditionProfile::THUNDERSTORM;
            case 27: // storm
                return WeatherConditionProfile::WINDY;
            default:
                return WeatherConditionProfile::UNKNOWN;
        }
    }
}
