<?php

namespace Runalyze\Profile\Sport\Icon;

use Runalyze\Util\InterfaceChoosable;

class SportIconProfile implements InterfaceChoosable
{
    public static function getChoices()
    {
        $choices = [];
        $iconClasses = [
            'icons8-Sports-Mode',
            'icons8-Running',
            'icons8-Regular-Biking',
            'icons8-Swimming',
            'icons8-Yoga',
            'icons8-Climbing',
            'icons8-Dancing',
            'icons8-Exercise',
            'icons8-Football',
            'icons8-Guru',
            'icons8-Handball',
            'icons8-Mountain-Biking',
            'icons8-Paddling',
            'icons8-Pilates',
            'icons8-Pushups',
            'icons8-Regular-Biking',
            'icons8-Roller-Skating',
            'icons8-Rowing',
            'icons8-Time-Trial-Biking',
            'icons8-Trekking',
            'icons8-Walking',
            'icons8-Weightlift',
            'icons8-skiing',
            'icons8-Mountaineering',
            'icons8-SnowShoeing',
            'icons8-CrossCountrySkiing',
            'icons8-Boating',
            'icons8-Rock-Climbing',
            'icons8-Golf',
            'icons8-Kayaking',
            'icons8-SnowBoarding',
            'icons8-Surfing',
            'icons8-WindSurfing',
            'icons8-Tennis',
            'icons8-Free-Swimming',
            'icons8-Bouldern'
        ];

        foreach ($iconClasses as $class) {
            $choices[$class] = $class;
        }

        return $choices;
    }
}
