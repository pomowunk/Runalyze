<?php
/**
 * This file contains class::PerformanceCondition
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Runalyze\Configuration;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity;

/**
 * Plot for: PerformanceCondition
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class PerformanceCondition extends ActivitySeries {
	/**
	 * @var string
	 */
	const COLOR = 'rgb(100,0,200)';

	/**
	 * Create series
	 * @var \Runalyze\View\Activity\Context $context
	 */
	public function __construct(Activity\Context $context) {
		$this->initOptions();
		$this->initData($context->trackdata(), Trackdata::PERFORMANCE_CONDITION);
	}


	/**
	 * Init options
	 */
	protected function initOptions() {
		$this->Label = __('Leistungszustand');
		$this->Color = self::COLOR;

		$this->UnitString = '';
		$this->UnitDecimals = 1;

		$this->TickSize = 5; // default bounds
		$this->TickDecimals = 0; // count fractional digits

		$this->ShowAverage = true;
		$this->ShowMaximum = false;
		$this->ShowMinimum = false;
	}

	/**
	 * Add to plot
	 * @param \Plot $Plot
	 * @param int $yAxis
	 * @param boolean $addAnnotations [optional]
	 */
	public function addTo(\Plot $Plot, $yAxis, $addAnnotations = true) {
        parent::addTo($Plot, $yAxis, $addAnnotations);

		if (!empty($this->Data)) {
            // #TSC different colored areas based on the garmin-connect documentation help
			$Plot->addMarkingArea('y'.$yAxis,  10.5,  20.5, 'rgba( 50, 255, 50, 0.30)'); // excellent
			$Plot->addMarkingArea('y'.$yAxis,   1.5,  10.5, 'rgba( 50, 255, 50, 0.15)'); // good
			$Plot->addMarkingArea('y'.$yAxis,   1.5,  -1.5, 'rgba(255, 255, 50, 0.10)'); // normal / initial state
			$Plot->addMarkingArea('y'.$yAxis,  -1.5, -10.5, 'rgba(255,  50, 50, 0.15)'); // sufficient
			$Plot->addMarkingArea('y'.$yAxis, -10.5, -20.5, 'rgba(255,  50, 50, 0.30)'); // bad
		}
	}
}
