<?php

namespace Runalyze\View;

class RpeColor
{
    /** @var array */
    const LEVEL_COLORS = [
        '#225ea8', // 1
        '#41b6c4', // 2
        '#41b6c4', // 3
        '#a1dab4', // 4
        '#a1dab4', // 5
        '#fecc5c', // 6
        '#fecc5c', // 7
        '#fd8d3c', // 8
        '#fd8d3c', // 9
        '#e31a1c'  // 10
    ];

	/** @var int|null */
	protected $Value = null;

    /**
	 * @param int|null $value
	 */
	public function __construct($value = null)
    {
		$this->setValue($value);
	}

	/**
	 * @param int|null $value
	 * @return self
	 */
	public function setValue($value)
    {
        if (!is_numeric($value) || $value < 1 || $value > 10) {
            $this->Value = null;
        } else {
            $this->Value = (int)$value;
        }

		return $this;
	}

    /**
     * @return int|null
     */
    public function value()
    {
        return $this->Value;
    }

	/**
	 * @return string
	 */
	public function borderColor()
    {
        if($this->Value != null) {
            return self::LEVEL_COLORS[$this->Value - 1];
        } else {
            return 'transparent';
        }
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function string()
    {
	    if (null === $this->Value) {
	        return '';
        }

        return '<span class="rpe-icon" style="border-color:'.$this->borderColor().';">'.$this->Value.'</span>';
	}
}
