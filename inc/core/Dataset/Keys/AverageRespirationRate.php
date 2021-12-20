<?php
/**
 * This file contains class::AverageRespirationRate
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: AverageRespirationRate
 * 
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class AverageRespirationRate extends AbstractKey
{
	/**
	 * Enum id
	 * @return int
	 */
	public function id()
	{
		return \Runalyze\Dataset\Keys::AVG_RESPIRATION_RATE;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column()
	{
		return 'avg_respiration_rate';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label()
	{
		return __('Avg respiration rate').' '.__('(by file)');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel()
	{
		return __('Avg respiration rate');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function description()
	{
		return __(
			'Die Atemfrequenz ist ein Messwert für die Anzahl der Atemzüge pro Minute (brpm) während des Tages. '.
			'Ein Atemzug umfasst sowohl das Einatmen als auch das Ausatmen.'
		);
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		return $context->activity()->avgRespirationRate();
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