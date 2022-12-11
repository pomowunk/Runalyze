<?php
/**
 * This file contains class::PaceAndHeartrateAndElevation
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for: Pace and heartrate and elevation
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class PaceAndHeartrateAndElevation extends ActivityPlot {
	/**
	 * Set key
	 */
	protected function setKey() {
		$this->key = 'pacehrelevation';
	}

	/**
	 * Init data
	 * @param \Runalyze\View\Activity\Context $context
	 */
	protected function initData(Activity\Context $context) {
		$Gradient = new Series\Gradient($context);
		/** @var \Runalyze\View\Plot\Series[] $allSeries */
		$allSeries = [
			new Series\Elevation($context),
			$Gradient,
			new Series\Heartrate($context),
			new Series\Pace($context)
		];

		$this->addMultipleSeries($allSeries);

		// #TSC add GAP, but not via addMultipleSeries, bacause yAxis must the same as the Pace series
		$GradeAdjustedPaceSeries = new Series\GradeAdjustedPace($context);
		$this->addSeries($GradeAdjustedPaceSeries, 4, false);

		$this->Plot->Options['legend']['hidden'] = [$Gradient->label(), $GradeAdjustedPaceSeries->label()]; // hide by default

	}
}
