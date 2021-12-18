<?php
/**
 * This file contains class::RespirationRate
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Runalyze\Configuration;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity;

/**
 * Plot for: RespirationRate
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class RespirationRate extends ActivitySeries {
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
		$this->initData($context->trackdata(), Trackdata::RESPIRATION_RATE);
	}


	/**
	 * Init options
	 */
	protected function initOptions() {
		$this->Label = __('Atemfrequenz');
		$this->Color = self::COLOR;

		$this->UnitString = 'brpm';
		$this->UnitDecimals = 1;

		$this->TickSize = 10;
		$this->TickDecimals = 0;

		$this->ShowAverage = true;
		$this->ShowMaximum = true;
		$this->ShowMinimum = true;
	}
}
