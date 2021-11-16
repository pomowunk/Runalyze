<?php

namespace Runalyze\Calculation\Power;

use Runalyze\Model\Trackdata;
use Runalyze\Model\Route;
use Runalyze\Mathematics\Distribution\TimeSeries;

abstract class AbstractPowerCalculator {
	/**
	 * @var int Limit for decently sane power values at pauses/glitches, assuming 25 W/kg (and 100 kg) for shortest possible efforts.
	 * @see https://blog.stryd.com/2019/12/06/what-are-the-human-limits-of-running-power/
	 */
	const MAX_POWER_LIMIT = 2500;
	
	/**
	 * @var Trackdata\Entity
	 */
	protected $Trackdata;

	/**
	 * @var Route\Entity
	 */
	protected $Route;

	/**
	 * @var int
	 */
	protected $Size;

	/**
	 * @var int[]
	 */
	protected $Power = [];

	/**
	 * Calculator for activity properties
	 * @param Trackdata\Entity $trackdata
	 * @param Route\Entity|null $route
	 */
	public function __construct(
		Trackdata\Entity $trackdata,
		Route\Entity $route = null
	) {
		$this->Trackdata = $trackdata;
		$this->Route = $route;

		$this->Size = $trackdata->num();
	}

	/**
	 * @return boolean
	 */
	protected function knowsRoute() {
		return (null !== $this->Route);
	}

	/**
	 * Calculate power array
	 *
	 * @param float $weight [kg] Weight of athlete and equipment
	 * @param float $powerFactor constant factor
	 * @return int[]
	 */
	abstract public function calculate($weight = 75.0, $powerFactor = 1.0);

	/**
	 * @return int[]
	 */
	public function powerData() {
		return $this->Power;
	}

	/**
	 * Calculate average power
	 * @return int [W]
	 */
	public function average() {
		if (empty($this->Power)) {
			return 0;
		}

		$Series = new TimeSeries($this->Power, $this->Trackdata->time());
		$Series->calculateStatistic();

		return (int)round($Series->mean());
	}
}
