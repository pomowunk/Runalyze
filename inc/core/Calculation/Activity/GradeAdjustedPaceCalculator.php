<?php
/**
 * This file contains class::PaceCalculator
 * @package Runalyze\Calculation\Activity
 */

namespace Runalyze\Calculation\Activity;

use Runalyze\Model\Trackdata;
use Runalyze\View\Activity;
use Runalyze\Calculation;
use Runalyze\Sports\Running\GradeAdjustedPace\Algorithm\Minetti;

/**
 * Calculate pace with grade adjustment based on Minetti.
 * It exists already the "grade adjusted pace" in Runalyze 4.3.0 - details 
 * inc/core/Sports/Running/GradeAdjustedPace/Algorithm/Minetti.php.
 * This (and the underlying algorithm from Minetti) only works for running!!! (not walking or hiking)
 *
 * see also:
 * https://journals.physiology.org/doi/full/10.1152/japplphysiol.01177.2001
 * https://forum.runalyze.com/viewtopic.php?f=35&t=566&p=2458&hilit=gap#p2458
 * https://github.com/gerhardol/trails/blob/master/Data/SpeedGradeAdjust.cs
 * 
 * @author codeproducer
 * @package Runalyze\Calculation\Activity
 */
class GradeAdjustedPaceCalculator {

	/**
	 * @var \Runalyze\Model\Trackdata\Entity
	 */
	protected $Trackdata;

    /**
     * @var \Runalyze\Model\Route\Entity
     */
    protected $Route;

	/**
	 * @var \Runalyze\Model\Trackdata\Loop
	 */
	protected $Loop;

	/**
	 * @var int|float
	 */
	protected $StepSize = 1;

	/**
     * the result: grade adjusted time
     *
	 * @var float
	 */
    protected $time;

    protected $KernelWidth = 20;

	/**
	 * Calculate the pace
     * 
	 * @param Runalyze\View\Activity\Context $context
	 */
	public function __construct(Activity\Context $context) {
        $trackdata = $context->trackdata();

		$this->Trackdata = $trackdata;
		$this->Loop = new Trackdata\Loop($trackdata);
        $this->Route = $context->route();
	}

    /**
     * get the gradient series from elevation/distance as numbers from -90 to +90.
     * same as GradeAdjustedPace.php.
     * 
     * @param Activity\Context $context
     * @return array
     */
    protected function getGradientSeries()
    {
        $gradient = new Calculation\Route\Gradient();
        $gradient->setDataFrom($this->Route, $this->Trackdata);
        $gradient->setMovingAverageKernel(new Calculation\Math\MovingAverage\Kernel\Uniform($this->KernelWidth));
        $gradient->calculate();

        return $gradient->getSeries();
    }

	/**
	 * Calculate the pace
	 */
	protected function calculate() {
        $pace = array();

        // calculate the grade adjusted pace
        $algorithm = new Minetti();
        $gradient = $this->getGradientSeries();

        // calculate the adjusted time based on the original-time array (from continuous data)
        $key = Trackdata\Entity::TIME;

        $gapTime = 0.0;
        while (!$this->Loop->isAtEnd()) {
            $this->Loop->move($key, $this->StepSize);

            // calculate time factor from Minetti's algorithm based on the gradient
            $i = $this->Loop->index();
            $f = $algorithm->getTimeFactor($gradient[$i] / 100.0);

            // factorize the time-point...and summarize it
            $gapTime +=  $this->Loop->difference(Trackdata\Entity::TIME) * $f;
		}

        // save
        $this->time = $gapTime;
    }

	/**
	 * @return the grade adjusted pace
	 */
	public function getAdjustedTime() {
        if (!isset($this->time)) {
            $this->calculate();
        }

		return $this->time;
	}
}