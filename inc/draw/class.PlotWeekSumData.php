<?php
/**
 * This file contains class::PlotWeekSumData
 * @package Runalyze\Plot
 */

use Runalyze\Parameter\Application\WeekStart;
use Runalyze\Util\Time;
use Runalyze\Util\LocalTime;

/**
 * Plot week data
 * @package Runalyze\Plot
 */
class PlotWeekSumData extends PlotSumData {
	protected WeekStart $WeekStart;

	public function __construct() {
		$this->timerStart = 1;
		$this->WeekStart = Runalyze\Configuration::General()->weekStart();

		if (Request::param('y') == self::LAST_6_MONTHS) {
			$this->timerEnd = 26;
		} else {
			if (Request::param('y') == self::LAST_12_MONTHS) {
				$yearEnd = (int)date('Y') - 1;
			} else {
				$yearEnd = (int)Request::param('y');
			}

			$this->timerEnd = (int)date("W", mktime(0, 0, 0, 12, 28, $yearEnd)); // http://de.php.net/manual/en/function.date.php#49457
		}

		parent::__construct();
	}

	protected function getCSSid(): string {
		return 'weekKM'.$this->Year.'_'.$this->Sport->id();
	}

	protected function getTitle(): string {
		return __('Weekly chart:');
	}

	protected function getXLabels(): array {
		$weeks = array();
		$add = ($this->Year == parent::LAST_6_MONTHS || $this->Year == parent::LAST_12_MONTHS) ? 0 : $this->WeekStart->phpWeek() - $this->timerEnd;
		$yearDiff = $add == 0 ? 0 : (int)date('Y') - (int)$this->Year;

		for ($w = $this->timerStart; $w <= $this->timerEnd; $w++) {
			$time = strtotime($this->WeekStart->lastDayOfWeekForStrtotime()." -".($this->timerEnd - $w + $add)." weeks -".$yearDiff." years");
			$string = (date("d", $time) <= 7 || $w == $this->timerStart) ? Time::month(date("m", $time), true) : '';

			if ($string != '' && date("m", $time) == 1) {
				$string .= ' \''.date("y", $time);
			}

			$weeks[] = array($w-$this->timerStart, $string);
		}

		return $weeks;
	}

	/**
	 * Timer table for query
	 */
	protected function timer(): string {
		if ($this->Year == parent::LAST_6_MONTHS || $this->Year == parent::LAST_12_MONTHS) {
			return '(('.$this->WeekStart->mysqlWeek('FROM_UNIXTIME(`time`)').' + '.$this->timerEnd.' - '.$this->WeekStart->phpWeek().' - 1)%'.$this->timerEnd.' + 1)';
		}

		return $this->WeekStart->mysqlWeek('FROM_UNIXTIME(`time`)');
	}

    protected function whereDate(): string {
        if (is_numeric($this->Year)) {
            $dateStart = (new LocalTime())->setISODate((int)$this->Year, 1, 1 + $this->WeekStart->differenceToMondayInDays())->setTime(0, 0, 0)->getTimestamp();
            $dateEnd = (new LocalTime())->setISODate((int)$this->Year + 1, 0, 7 + $this->WeekStart->differenceToMondayInDays())->setTime(23, 59, 59)->getTimestamp();

            return '`time` BETWEEN '.$dateStart.' AND '.$dateEnd;
        }

        return parent::whereDate();
    }

	protected function beginningOfLast6Months(): int {
		return $this->beginningOfTimerange();
	}

	protected function beginningOfLast12Months(): int {
		return $this->beginningOfTimerange();
	}

	protected function beginningOfTimerange(): int {
		if ($this->WeekStart->isSunday() && date('w') != 0) {
			return LocalTime::fromString($this->WeekStart->firstDayOfWeekForStrtotime()." -".$this->timerEnd." weeks 00:00")->getTimestamp();
		}

		return LocalTime::fromString($this->WeekStart->firstDayOfWeekForStrtotime()." -".($this->timerEnd - 1)." weeks 00:00")->getTimestamp();
	}

	protected function factorForWeekKm(): float {
		return 1;
	}
}
