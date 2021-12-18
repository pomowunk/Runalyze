<?php
/**
 * This file contains class::PerformanceCondition
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for: PerformanceCondition
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class PerformanceCondition extends ActivityPlot {
	/**
	 * Set key
	 */
	protected function setKey() {
		$this->key   = 'performancecondition';
	}

	/**
	 * Init data
	 * @param \Runalyze\View\Activity\Context $context
	 */
	protected function initData(Activity\Context $context) {
		$this->addSeries(
			new Series\PerformanceCondition($context)
		);
	}
}
