<?php

namespace Runalyze\Calculation\Power;

use Runalyze\Model\Trackdata;
use Runalyze\Calculation\Math\MovingAverage\WithKernel;
use Runalyze\Calculation\Math\MovingAverage\Kernel;

class RunningPowerCalculator extends AbstractPowerCalculator {
	/**
	 * @var int Skiba recommends a 120s moving average window, but RUNALYZE (online) seems to use around 30s.
	 */
	const AVERAGING_WINDOW_SECONDS = 120;
	
	/**
	 * @see https://github.com/GoldenCheetah/GoldenCheetah/blob/master/src/Metrics/GOVSS.cpp
	 * @param float $speed [m/s]
	 * @param float $speed_before [m/s] Speed at last point
	 * @param float $slope [m/m]
	 * @param float $distance [m] Distance since last point
	 * @param float $weight [kg] Weight of runner and equipment
	 * @param float $height [m] Height of runner
	 * @return float [W] estimated running power
	 */
	protected function calculate_power($speed, $speed_before, $slope, $distance, $weight, $height=1.8) {
		/**
		 * Aero contribution per kg: "Modeling the energetics of 100-m running by using speed curves
		 * of world champions" (Arsac 2001)
		 * @see http://runscribe.com/wp-content/uploads/power/Arsac2001.pdf
		 */
		$Af = (0.2025 * pow($height, 0.725) * pow($weight, 0.425)) * 0.266; # Frontal Area
		$cAero = 0.5 * 1.2 * 0.9 * $Af * $speed * $speed / $weight;

		/**
		 * Kinetic Energy: "Modeling the energetics of 100-m running by using speed curves
		 * of world champions" (Arsac 2001)
		 * @see http://runscribe.com/wp-content/uploads/power/Arsac2001.pdf
		 */
		$cKin = $distance > 0.0 ? 0.5 * ($speed * $speed - $speed_before * $speed_before) / $distance : 0.0;

		/**
		 * "Energy cost of walking and running at extreme uphill and downhill slopes" (Minetti 2002)
		 * @see https://pubmed.ncbi.nlm.nih.gov/12183501/
		 */ 
		$cSlope = ((((155.4 * $slope - 30.4) * $slope - 43.3) * $slope + 46.3) * $slope + 19.5) * $slope + 3.6;

		/**
		 * Efficiency: "Calculation of Power Output and Quantification of Training Stress
		 * in Distance Runners: The Development of the GOVSS Algorithm" (Skiba 2006)
		 * @see https://runscribe.com/wp-content/uploads/power/GOVSS.pdf
		 */
		$eff = (0.25 + 0.054 * $speed) * (1 - 0.5 * $speed / 8.33);

		return ($cAero + $cKin + $cSlope * $eff) * $speed * $weight;
	}
	
	/**
	 * Calculate power array
	 *
	 * @param float $weight [kg] Weight of runner and equipment
	 * @param float $powerFactor constant factor
	 * @return int[]
	 */
	public function calculate($weight = 75.0, $powerFactor = 1.0) {
		if (!$this->Trackdata->has(Trackdata\Entity::TIME) || !$this->Trackdata->has(Trackdata\Entity::DISTANCE)) {
			return [];
		}

		$ArrayTime = $this->Trackdata->time();
		$ArrayDist = $this->Trackdata->distance();
		$ArrayElev = $this->knowsRoute() ? $this->Route->elevations() : [];
		
		$deltaDist = [];
		for ($i = 0; $i < $this->Size; $i++) {
			$deltaDist[$i] = 1000 * ($ArrayDist[max(1,$i)] - $ArrayDist[max(1,$i) - 1]);
		}
		
		$deltaTime = [];
		for ($i = 0; $i < $this->Size; $i++) {
			$deltaTime[$i] = $ArrayTime[max(1,$i)] - $ArrayTime[max(1,$i) - 1];
		}
		
		$slope = [];
		if (!empty($ArrayElev)) {
			// Moving average over slope
			$ArraySlope = [];
			for ($i = 0; $i < $this->Size; $i++) {
				$height_meters = $ArrayElev[max(1,$i)] - $ArrayElev[max(1,$i) - 1];
				$ArraySlope[$i] = $deltaDist[$i] > 0.0 ? $height_meters / $deltaDist[$i] : ($i > 0 ? $ArraySlope[$i - 1] : 0.0);
			}
			$slope_averager = new WithKernel($ArraySlope, $ArrayTime);
			$slope_averager->setKernel(new Kernel\Uniform(self::AVERAGING_WINDOW_SECONDS));
			$slope_averager->calculate();
			$slope = $slope_averager->movingAverage();
		}
		
		// Moving average over speed
		$ArraySpeed = [];
		for ($i = 0; $i < $this->Size; $i++) {
			$ArraySpeed[$i] = $deltaTime[$i] > 0 ? $deltaDist[$i] / $deltaTime[$i] : ($i > 0 ? $ArraySpeed[$i - 1] : 0.0);
		}
		$speed_averager = new WithKernel($ArraySpeed, $ArrayTime);
		$speed_averager->setKernel(new Kernel\Uniform(self::AVERAGING_WINDOW_SECONDS));
		$speed_averager->calculate();
		$speed = $speed_averager->movingAverage();
		
		for ($i = 0; $i < $this->Size; $i++) {
			if ($deltaTime[$i] > 0) {
				$current_slope = empty($slope) ? 0.0 : $slope[$i];
				$calculated_power = $this->calculate_power($speed[max(1,$i)], $speed[max(1,$i)-1], $current_slope, $deltaDist[$i], $weight);
				$this->Power[] = (int)round(min(max($powerFactor * $calculated_power, 0), self::MAX_POWER_LIMIT));
			} else {
				$this->Power[] = 0;
			}
		}
		
		return $this->Power;
	}
}
