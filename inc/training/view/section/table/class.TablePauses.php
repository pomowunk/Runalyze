<?php
/**
 * This file contains class::TableLapsComputed
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity\Context;
use Runalyze\Model\Trackdata\Pauses;
use Runalyze\Activity\Duration;
use Runalyze\Activity\HeartRate;

/**
 * Display pauses
 *
 * @author codeproducer
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TablePauses {
	/**
	 * Code
	 * @var string
	 */
	protected $Code = '';

	/**
	 * @param \Runalyze\View\Activity\Context $context
	 */
	public function __construct(Context $context) {
		$this->setDataToCode($context->trackdata()->pauses(), $context->Activity()->elapsedTime());
	}

	/**
	 * Get code
	 * @return string
	 */
	public function getCode() {
		return $this->Code;
	}

	/**
	 * generate data
	 */
	protected function setDataToCode(Pauses $pauses, float $fullTime) {
		// header
		$this->Code .= '<table class="fullwidth zebra-style">';
		$this->Code .= '<thead><tr>';
		$this->Code .= '<th class="r">#</th>';
		$this->Code .= '<th class="r">'. __('Time') .'</th>';
		$this->Code .= '<th class="r">'. __('Duration') .'</th>';
		$this->Code .= '<th class="r">'. __('Heart rate') . ' (' . __('Start') . ')</th>';
		$this->Code .= '<th class="r">'. __('Difference').'</th>';
		$this->Code .= '<th class="r" style="padding-right: 20px;">'. __('Heart rate') . ' (' . __('End') . ')</th>';
		$this->Code .= '</tr></thead>';

		// pauses
		$this->Code .= '<tbody>';

		$len = count($pauses->asArray());
		$hr = new HeartRate(0, Runalyze\Context::Athlete());
		$allPause = 0;

		for ($i = 0; $i < $len; $i++) {
			$this->Code .= '<tr class="r">';

			$pause = $pauses->at($i);

			$this->Code .= '<td>'. ($i + 1) .'.</td>';
			$this->Code .= '<td>'. Duration::format($pause->time()) .'</td>';
			$this->Code .= '<td>'. Duration::format($pause->duration()) .'</td>';

			if ($pause->hasHeartRateInfo()) {
				$hr->setBPM($pause->HRstart());
				$this->Code .= '<td>'. ($hr->inBPM() > 0 ? $hr->string() : '-') .'</td>';

				$hr->setBPM(- $pause->hrDiff() );
				$this->Code .= '<td>'. ($hr->inBPM() != 0 ? $hr->string() : '-') .'</td>';

				$hr->setBPM($pause->HRend());
				$this->Code .= '<td style="padding-right: 20px;">'. ($hr->inBPM() > 0 ? $hr->string() : '-') .'</td>';
			} else {
				$this->Code .= '<td colspan="3"></td>';
			}

			$allPause += $pause->duration();
			$this->Code .= '</tr>';
		}
		$this->Code .= '</tbody>';

		// summary
		$this->Code .= '<tbody>';
		$this->Code .= '<tr class="r" style="border-top: 1px solid #666;">';
		$this->Code .= '<td colspan="2"></td>';
		$this->Code .= '<td>'. Duration::format($allPause) .'</td>';
		$this->Code .= '<td colspan="3" style="padding-right: 20px;"></td>';
		$this->Code .= '</tr>';

		// fullTime must be set, but go to secure side ;-)
		if ($fullTime > 0) {
			$this->Code .= '<tr class="r">';
			$this->Code .= '<td colspan="2"></td>';
			$this->Code .= '<td>'. round(100 / $fullTime * $allPause) .'% of '. Duration::format($fullTime) .'</td>';
			$this->Code .= '<td colspan="3" style="padding-right: 20px;"></td>';
			$this->Code .= '</tr>';
		}
		$this->Code .= '</tbody>';

		$this->Code .= '</table>';
	}
}