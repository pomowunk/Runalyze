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
	 * the same range for Longitude (and Altitude). Hope that the two values has the same NULLs.
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
                }
            }
        }
    }

	/**
	 * Ensure that on first/pre and last/post values of a array there are no NULL values.
     * So search for the "first/last" valid value and replace the NULLs.
	 * #TSC
	 */
    public function correctArraySurroundedNulls(array &$values)
    {
        if (is_array($values) && !empty($values)) {
            $size = count($values);

            // pre NULLs

            // search for the index with the first NOT NULL value
            $firstNotNull = 0;
            while($firstNotNull < $size && is_null($values[$firstNotNull])) {
                $firstNotNull++;
            }

            if($firstNotNull > 0 && $firstNotNull < $size) {
                for($i = 0; $i < $firstNotNull; $i++) {
                    $values[$i] = $values[$firstNotNull];
                }
            }

            // post NULLs

            // search for the index with the last NOT NULL value (backwards)
            $lastNotNull = $size - 1;
            while($lastNotNull > 0 && is_null($values[$lastNotNull])) {
                $lastNotNull--;
            }

            // yes, we have valid values. remove the invalid NULLs
            if($lastNotNull < $size - 1) {
                for($i = $size - 1; $i > $lastNotNull; $i--) {
                    $values[$i] = $values[$lastNotNull];
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

    /**
     * Returns the temperature from the continuous-data if available.
     * #TSC
     */
    public function getAverageTemperatur() {
        if (!empty($this->ContinuousData->Temperature)) {
            $a = array_filter($this->ContinuousData->Temperature);
            $average = array_sum($a)/count($a);
            return $average;
        } else {
            return NULL;
        }
    }
}
