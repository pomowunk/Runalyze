<?php
/**
 * This file contains the class::SummaryTableAllYears
 * @package Runalyze\Plugins\Stats\RunalyzePluginStat_Statistiken
 */

use Runalyze\Util\Time;
use Runalyze\Util\LocalTime;

/**
 * Summary table for dataset/data browser
 * 
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Stats\RunalyzePluginStat_Statistiken
 */
class SummaryTableMonths extends SummaryTable {
	/**
	 * @var int
	 */
	const MODE_LAST_6 = 0;

	/**
	 * @var int
	 */
	const MODE_LAST_12 = 1;

	/**
	 * @var int
	 */
	const MODE_ALL = 2;

	/**
	 * @var int
	 */
	const MODE_YEAR = 3;

	/**
	 * @var int
	 */
	protected $Mode = 0;

	/**
	 * @param int $mode
	 */
	public function setMode($mode) {
		$this->Mode = $mode;
	}

	/**
	 * Prepare summary
	 */
	protected function prepare() {
		$this->Timerange = 31*DAY_IN_S;

		switch ($this->Mode) {
			case self::MODE_LAST_6:
				$this->Title = __('Last 6 months');
				$this->TimeEnd = LocalTime::mktime(23, 59, 59, (int)date('m')+1, 0, (int)date('Y'));
				$this->TimeStart = LocalTime::mktime(0, 0, 1, (int)date('m')-6, 1, (int)date('Y'));
				break;

			case self::MODE_LAST_12:
				$this->Title = __('Last 12 months');
				$this->TimeEnd = LocalTime::mktime(23, 59, 59, (int)date('m')+1, 0, (int)date('Y'));
				$this->TimeStart = LocalTime::mktime(0, 0, 1, (int)date('m')-12, 1, (int)date('Y'));
				break;

			case self::MODE_ALL:
				$this->Title = __('All months');
				$this->TimeEnd = (new LocalTime)->yearEnd();
				$this->TimeStart = (new LocalTime(START_TIME))->yearStart();
				break;

			case self::MODE_YEAR:
				$this->Title = (string)$this->Year;
				$this->TimeEnd = LocalTime::mktime(23, 59, 59, 12, 31, $this->Year);
				$this->TimeStart = LocalTime::mktime(0, 0, 1, 1, 1, $this->Year);
				break;
		}
	}

	/**
	 * Head for row
	 * @param int $index
	 * @return string
	 */
	protected function rowHead($index) {
		$midOfTimerange = (int)round($this->TimeEnd - ($index + 0.5) * 31 * DAY_IN_S);
		$month = date('m', $midOfTimerange);

		return DataBrowserLinker::monthLink(Time::month($month), $midOfTimerange);
	}
}
