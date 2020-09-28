<?php
/**
 * This file contains class::FitAnaerobicTrainingEffect
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: FitAnaerobicTrainingEffect
 * 
 * #TSC: this class contains the labels/short/description for main-data-table, and the dataview (used to select the attributes/position for main-table).
 * 
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class FitAnaerobicTrainingEffect extends AbstractKey
{
	/**
	 * Enum id
	 * @return int
	 */
	public function id()
	{
		return \Runalyze\Dataset\Keys::FIT_ANAEROBIC_TRAINING_EFFECT;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column()
	{
		return 'fit_anaerobic_training_effect';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label()
	{
        // #TSC: label on the dataview-configuration screen
		return __('Anaerob Training Effect').' '.__('(by file)');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel()
	{
        // #TSC: short-label for main-table
		return __('ATE');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function description()
	{
		return __(
			'Anaerobic Training Effect is an indicator between 0.0 (none) and 5.0 (overreaching) '.
			'to rate the impact of anaerobic exercise on your body.'
		);
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		return $context->dataview()->fitAnaerobicTrainingEffect();
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function cssClass()
	{
		return 'small';
	}
}
