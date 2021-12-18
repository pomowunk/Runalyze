<?php
/**
 * This file contains class::RespirationRate
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for: RespirationRate
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class RespirationRate extends ActivityPlot {
	/**
	 * Set key
	 */
	protected function setKey() {
		$this->key   = 'respirationrate';
	}

	/**
	 * Init data
	 * @param \Runalyze\View\Activity\Context $context
	 */
	protected function initData(Activity\Context $context) {
		$this->addSeries(
			new Series\RespirationRate($context)
		);
	}
}
