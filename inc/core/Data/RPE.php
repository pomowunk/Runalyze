<?php

namespace Runalyze\Data;

class RPE {
    public static function completeList(): array
    {
        return array(
            6 => '6 - '.__('No exertion at all'),
            7 => '7 - '.__('Extremely light'),
            8 => '8',
            9 => '9 - '.__('Very light'),
            10 => '10',
            11 => '11 - '.__('Light'),
            12 => '12',
            13 => '13 - '.__('Somewhat hard'),
            14 => '14',
            15 => '15 - '.__('Hard'),
            16 => '16',
            17 => '17 - '.__('Very Hard'),
            18 => '18',
            19 => '19 - '.__('Extremely hard'),
            20 => '20 - '.__('Maximal exertion')
        );
    }

    public static function validRPE($value)
    {
        return $value >=6 && $value <=20;
    }

    public static function getString($value)
    {
        return self::validRPE($value) ? self::completeList()[$value] : '';
    }
}
