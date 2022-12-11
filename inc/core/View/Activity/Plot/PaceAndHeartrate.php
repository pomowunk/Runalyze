<?php
/**
 * This file contains class::PaceAndHeartrate
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for: Pace and heartrate
 * 
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class PaceAndHeartrate extends ActivityPlot {
	/**
	 * Set key
	 */
	protected function setKey() {
		$this->key = 'pacehr';
	}

	/**
	 * Init data
	 * @param \Runalyze\View\Activity\Context $context
	 */
	protected function initData(Activity\Context $context) {
		$allSeries = [
			new Series\Pace($context),
			new Series\Heartrate($context)
		];

		$this->addMultipleSeries($allSeries);
		
		// #TSC add GAP, but not via addMultipleSeries, bacause yAxis must the same as the Pace series
		$GradeAdjustedPaceSeries = new Series\GradeAdjustedPace($context);
		$this->addSeries($GradeAdjustedPaceSeries, 1, false);

		$this->Plot->Options['legend']['hidden'] = [$GradeAdjustedPaceSeries->label()]; // hide GAP by default
	}
}
