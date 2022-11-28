<?php
/**
 * This file contains class::Temperature
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: Temperature
 * 
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Temperature extends AbstractKey
{
	/**
	 * Enum id
	 * @return int
	 */
	public function id()
	{
		return \Runalyze\Dataset\Keys::TEMPERATURE;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column()
	{
		return 'temperature';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label()
	{
		return __('Temperature');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel()
	{
		return __('Temp.');
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		// #TSC show the temperatur in the dataset if temp is available; the original code only shows in case of "not indoor"
		if (!empty($context->activity()->weather()->temperature())) {
			return \Runalyze\Activity\Temperature::format(
				$context->activity()->weather()->temperature()->value(),
				true,
				false
			);
		}

		return '';
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode()
	{
		return SummaryMode::AVG_WITHOUT_NULL;
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function cssClass()
	{
		return 'small';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function defaultCssStyle()
	{
		return 'width:35px;';
	}
}
