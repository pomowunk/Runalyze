<?php

namespace Runalyze\Parser\Activity\Common\Data;

use Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection;

class ContinuousDataAdapter
{
    /** @var int */
    const RPM_LIMIT_FOR_CORRECTION = 130;

    /** @var ContinuousData */
    protected $ContinuousData;

    public function __construct(ContinuousData $continuousData)
    {
        $this->ContinuousData = $continuousData;
    }

    public function calculateDistancesIfRequired()
    {
        if ($this->distancesShouldBeCalculated()) {
            (new GpsDistanceCalculator())->calculateDistancesFor($this->ContinuousData);
        }
    }

    /**
     * @return bool
     */
    protected function distancesShouldBeCalculated()
    {
        return (
            empty($this->ContinuousData->Distance) &&
            !empty($this->ContinuousData->Latitude) &&
            !empty($this->ContinuousData->Longitude)
        );
    }

    /**
     * @see https://github.com/Runalyze/Runalyze/issues/1367
     */
    public function correctCadenceIfRequired()
    {
        if (!empty($this->ContinuousData->Cadence)) {
            $avg = array_sum($this->ContinuousData->Cadence) / count($this->ContinuousData->Cadence);

            if ($avg > self::RPM_LIMIT_FOR_CORRECTION) {
                $this->ContinuousData->Cadence = array_map(function ($v) {
                    return round($v / 2);
                }, $this->ContinuousData->Cadence);
            }
        }
    }

	/**
	 * Corrects the problem that the GPS device has NULL values while GPS is not fully initializes while 
	 * activity is tracking.
	 * so search for the first Latitude with NOT NULL and copy these value to the NULLs. Do this also with
	 * the same range for Longitude (and Altitude). Hope that all 3 values has the same NULLs.
	 * #TSC
	 */
    public function correctPreNullGpsIfRequired()
    {
        if (!empty($this->ContinuousData->Latitude)) {
			$size = count($this->ContinuousData->Latitude);
			//search for the index with the first NOT NULL value
			$firstNotNull = 0;
			while($firstNotNull < $size && is_null($this->ContinuousData->Latitude[$firstNotNull])) {
				$firstNotNull++;
			}
            
			if($firstNotNull > 0 && $firstNotNull < $size) {
				for($i = 0; $i < $firstNotNull; $i++) {
					$this->ContinuousData->Latitude[$i]  = $this->ContinuousData->Latitude[$firstNotNull];
					$this->ContinuousData->Longitude[$i] = $this->ContinuousData->Longitude[$firstNotNull];
                    
                    // special case altitude: if using baro, the altitude can exist while lat/long is NULL
                    if(is_null($this->ContinuousData->Altitude[$i])) {
                        $this->ContinuousData->Altitude[$i]  = $this->ContinuousData->Altitude[$firstNotNull];
                    }
				}
			}
        }
    }

	/**
	 * On the Fenix 6 sometimes the last heart-rate is NULL. Correct this here to get the last NOT NULL value
     * and put it on the last NULL's.
	 * #TSC
	 */
    public function correctNullHeartrateIfRequired()
    {
        if (!empty($this->ContinuousData->HeartRate)) {
			$size = count($this->ContinuousData->HeartRate);

            // pre NULLs

			//search for the index with the first NOT NULL value
			$firstNotNull = 0;
			while($firstNotNull < $size && is_null($this->ContinuousData->HeartRate[$firstNotNull])) {
				$firstNotNull++;
			}

			if($firstNotNull > 0 && $firstNotNull < $size) {
				for($i = 0; $i < $firstNotNull; $i++) {
                    $this->ContinuousData->HeartRate[$i] = $this->ContinuousData->HeartRate[$firstNotNull];
				}
			}
            
            // post NULLs

			//search for the index with the last NOT NULL value
			$lastNotNull = $size - 1;
			while($lastNotNull > 0 && is_null($this->ContinuousData->HeartRate[$lastNotNull])) {
				$lastNotNull--;
			}

            // yes, we have valid heart-rates. remove the invalid NULLs
			if($lastNotNull < $size - 1) {
				for($i = $size - 1; $i > $lastNotNull; $i--) {
					$this->ContinuousData->HeartRate[$i] = $this->ContinuousData->HeartRate[$lastNotNull];
				}
			}
        }
    }
        
    public function filterUnwantedZeros()
    {
        foreach ($this->ContinuousData->getPropertyNamesOfArraysThatShouldNotContainZeros() as $key) {
            if (!empty($this->ContinuousData->{$key})) {
                foreach ($this->ContinuousData->{$key} as $i => $value) {
                    if (0 == $value) {
                        $this->ContinuousData->{$key}[$i] = null;
                    }
                }
            }
        }
    }

    public function clearEmptyArrays()
    {
        $arrayKeys = $this->ContinuousData->getPropertyNamesOfArrays();
        $arrayKeysWithData = [];
        $arraySize = null;

        foreach ($arrayKeys as $key) {
            if (!empty($this->ContinuousData->{$key})) {
                $arrayKeysWithData[] = $key;

                if (null === $arraySize) {
                    $arraySize = count($this->ContinuousData->{$key});
                }
            }
        }

        for ($i = 0; $i < $arraySize; ++$i) {
            if (empty($arrayKeysWithData)) {
                return;
            }

            foreach ($arrayKeysWithData as $j => $key) {
                if (null !== $this->ContinuousData->{$key}[$i]) {
                    unset($arrayKeysWithData[$j]);
                }
            }
        }

        foreach ($arrayKeysWithData as $key) {
            $this->ContinuousData->{$key} = [];
        }

        if (!empty($this->ContinuousData->Distance) && 0 == end($this->ContinuousData->Distance)) {
            $this->ContinuousData->Distance = [];
        }
    }

    /**
     * @param PauseCollection $pausesToApply
     * @return PauseCollection
     */
    public function applyPauses(PauseCollection $pausesToApply)
    {
        $resultingPauses = new PauseCollection();

        $num = count($this->ContinuousData->Time);
        $numPauses = $pausesToApply->count();
        $keys = $this->ContinuousData->getPropertyNamesOfArrays();
        $hasHeartRate = !empty($this->ContinuousData->HeartRate);
        $hrStart = null;
        $pauseInSeconds = 0;
        $pauseIndex = 0;
        $pauseUntil = 0;
        $pauseTime = $pausesToApply[$pauseIndex]->getTimeIndex();
        $isPause = false;

        for ($i = 0; $i < $num; $i++) {
            if (!$isPause && $this->ContinuousData->Time[$i] > $pauseTime) {
                if ($pauseIndex < $numPauses) {
                    $isPause = true;
                    $hrStart = !$hasHeartRate ? null : (isset($this->ContinuousData->HeartRate[$i - 1]) ? $this->ContinuousData->HeartRate[$i - 1] : $this->ContinuousData->HeartRate[$i]);
                    $pauseInSeconds += $pausesToApply[$pauseIndex]->getDuration();
                    $pauseUntil = $pausesToApply[$pauseIndex]->getDuration() + $pausesToApply[$pauseIndex]->getTimeIndex();
                    $pauseIndex++;
                    $pauseTime = ($pauseIndex < $numPauses) ? $pausesToApply[$pauseIndex]->getTimeIndex() : PHP_INT_MAX;
                }
            }

            if ($isPause && $this->ContinuousData->Time[$i] >= $pauseUntil) {
                $isPause = false;
                $newPause = clone $pausesToApply[$pauseIndex - 1];
                $newPause->setHeartRateDetails($hrStart, $hasHeartRate ? $this->ContinuousData->HeartRate[$i] : null);

                $resultingPauses->add($newPause);
            }

            if ($isPause) {
                foreach ($keys as $key) {
                    if (array_key_exists($i, $this->ContinuousData->{$key})) {
                        unset($this->ContinuousData->{$key}[$i]);
                    }
                }
            } else {
                $this->ContinuousData->Time[$i] -= $pauseInSeconds;
            }
        }

        return $resultingPauses;
    }

    public function reIndexArrays()
    {
        foreach ($this->ContinuousData->getPropertyNamesOfArrays() as $key) {
            $this->ContinuousData->{$key} = array_merge($this->ContinuousData->{$key});
        }
    }
}
