<?php

namespace Runalyze\Data;

use Runalyze\Profile\FitSdk\StrokeTypeProfile;

class Stroketype
{
    /** @var int */
    protected $Identifier;

    /**
     * @param int $identifier a class constant
     */
    public function __construct($identifier)
    {
        $this->set($identifier);
    }

    /**
     * @param int $identifier a class constant
     */
    public function set($identifier)
    {
        if (in_array($identifier, self::completeList())) {
            $this->Identifier = $identifier;
        }
    }

    /**
     * @return array
     */
    public static function completeList()
    {
        return StrokeTypeProfile::getEnum();
    }

    /**
     * @return int
     */
    public function id()
    {
        return $this->Identifier;
    }

    /**
     * @return string
     */
    public function string()
    {
        switch ($this->Identifier) {
            case StrokeTypeProfile::FREESTYLE:
                return __('Freestyle');
            case StrokeTypeProfile::BACK:
                return __('Backstroke');
            case StrokeTypeProfile::BREAST:
                return __('Breaststroke');
            case StrokeTypeProfile::BUTTERFLY:
                return __('Butterfly');
            case StrokeTypeProfile::DRILL:
                return __('Drill');
            case StrokeTypeProfile::MIXED:
                return __('Mixed');
            case StrokeTypeProfile::BREAK:
                return __('Break');
        }

        return '';
    }

    /**
     * @return string
     */
    public function shortString()
    {
        switch ($this->Identifier) {
            case StrokeTypeProfile::FREESTYLE:
                return __('Freestyle');
            case StrokeTypeProfile::BACK:
                return __('Back');
            case StrokeTypeProfile::BREAST:
                return __('Breast');
            case StrokeTypeProfile::BUTTERFLY:
                return __('Butterfly');
            case StrokeTypeProfile::DRILL:
                return __('Drill');
            case StrokeTypeProfile::MIXED:
                return __('Mixed');
            case StrokeTypeProfile::BREAK:
                return __('Break');
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isBreak() {
        return $this->Identifier == StrokeTypeProfile::BREAK;
    }
}

