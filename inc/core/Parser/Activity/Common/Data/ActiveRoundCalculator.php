<?php
/**
 * This file contains class::ActiveRoundCalculator
 * @package Runalyze\Calculation\Activity
 */

namespace Runalyze\Parser\Activity\Common\Data;

use Runalyze\Model\Trackdata;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;

/**
 * Calculate values based only on active rounds. Inactive rounds will be ignored.
 * 
 * @author #TSC
 * @package Runalyze\Calculation\Activity
 */
class ActiveRoundCalculator {
	protected $continuousData;

    protected $activeIdx;

	public function __construct(ContinuousData $continuousData, RoundCollection $rounds) {
		$this->continuousData = $continuousData;

        if (!$rounds->isEmpty() ) {
            $this->init($rounds);
        }
	}

    /**
     * builds the activeIdx array this is corresponding with the other ContinuousData arrays.
     * for every index which is TRUE, the value is in a active round/lap.
     * 
     * data examples:
     * 
     * from the split/runalyze_training.splits:
     * round    duration    from-to
     * 1        549,92      0 - 549,92
     * 2        396,08      549,93 - 946,01 (add 0.01 to the duration of #1)
     * ...
     * 
     * from runalyze_tackdata.time; all in seconds
     * runalyze_tackdata    lap#    round split
     * time	                        time
     *
     * 38                   1       0
     * ...
     * 551                          549,92
     * 947                  break   549,93 (add 0,01 to duration) (+ 396,08) till 946,01
     * 974                  2       946,02
     * ...
     */
    protected function init(RoundCollection $rounds) {
        $time = $this->continuousData->Time;
        $len = count($time);

        // fill array with true's
        $this->activeIdx = array_fill(0, count($time), true);

        $roundTimeStart = 0;
        $timeIdx = 0;
        foreach($rounds as $key => $value) {
            // find the start of the inactive round
            if (!$value->IsActive()) {
                // time is trackdata second and roundTimeStart is starttime of the round
                while ($time[$timeIdx] <= $roundTimeStart && $timeIdx < $len) {
                    // this is the last trackdata-index for previous round
                    $timeIdx++;
                }
                // the differences between runalyze_tackdata.time and the "round splits" are fixed with this additional check
                if ($timeIdx > 0 && $roundTimeStart - $time[$timeIdx - 1] > $time[$timeIdx] - $roundTimeStart) {
                    $timeIdx++;
                }
                // now the idx is on the break start
            }

            // the rounds have only durations, so add the duration to get the "next round start time"
            $roundTimeStart += $value->getDuration() + 0.01;

            if (!$value->IsActive()) {
                // set all "inactive indexes" to false where time is below the "next round start time"
                while ($time[$timeIdx] <= $roundTimeStart && $timeIdx < $len - 1) {
                    $this->activeIdx[$timeIdx++] = false;
                };

                // the differences between runalyze_tackdata.time and the "round splits" are fixed with this additional check
                if ($roundTimeStart - $time[$timeIdx - 1] > $time[$timeIdx] - $roundTimeStart) {
                    $this->activeIdx[$timeIdx++] = false;
                }
            }

            if ($timeIdx >= $len) {
                break;
            }
        }
    }

    /**
     * returns the avg heart-rate of all active rounds.
     */
    public function calcAvgHeartRate() {
        $hr = $this->continuousData->HeartRate;

        if (empty($this->activeIdx) || empty($hr)) {
            return null;
        }

        $a = [];

        foreach($this->activeIdx as $key => $value) {
            if ($value) {
                $a[] = $hr[$key];
            }
        }

        if (count($a) > 0) {
            return round(array_sum($a) / count($a));
        } else {
            return null;
        }
    }
}
