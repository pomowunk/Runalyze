<?php

namespace Runalyze\Parser\Activity\Common\Data;

use Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollectionFiller;
use Runalyze\Parser\Activity\Common\Filter\FilterCollection;

class ActivityDataContainer
{
    /** @var Metadata */
    public $Metadata;

    /** @var ActivityData */
    public $ActivityData;

    /** @var ContinuousData */
    public $ContinuousData;

    /** @var ContinuousDataAdapter */
    protected $ContinuousDataAdapter;

    /** @var RoundCollection */
    public $Rounds;

    /** @var PauseCollection */
    public $Pauses;

    /** @var PauseCollection */
    public $PausesToApply;

    /** @var FitDetails */
    public $FitDetails;

    /** @var WeatherData */
    public $WeatherData;

    /** @var array */
    public $RRIntervals = [];

    public function __construct()
    {
        $this->Metadata = new Metadata();
        $this->ActivityData = new ActivityData();
        $this->ContinuousData = new ContinuousData();
        $this->ContinuousDataAdapter = new ContinuousDataAdapter($this->ContinuousData);
        $this->Rounds = new RoundCollection();
        $this->Pauses = new PauseCollection();
        $this->PausesToApply = new PauseCollection();
        $this->FitDetails = new FitDetails();
        $this->WeatherData = new WeatherData();
    }

    public function __clone()
    {
        $this->Metadata = clone $this->Metadata;
        $this->ActivityData = clone $this->ActivityData;
        $this->ContinuousData = clone $this->ContinuousData;
        $this->ContinuousDataAdapter = new ContinuousDataAdapter($this->ContinuousData);
        $this->Rounds = clone $this->Rounds;
        $this->Pauses = clone $this->Pauses;
        $this->PausesToApply = clone $this->PausesToApply;
        $this->FitDetails = clone $this->FitDetails;
        $this->WeatherData = clone $this->WeatherData;
    }

    public function completeContinuousData()
    {
        $this->ContinuousDataAdapter->filterUnwantedZeros();
        $this->ContinuousDataAdapter->clearEmptyArrays();
        $this->ContinuousDataAdapter->calculateDistancesIfRequired();
        $this->ContinuousDataAdapter->correctCadenceIfRequired();
        $this->ContinuousDataAdapter->correctPreNullGpsIfRequired();

        // TSC: On the Fenix 6 sometimes the last heart-rate is NULL. Correct this here to get the last NOT NULL value and put it on the last NULL's.
        $this->ContinuousDataAdapter->correctArraySurroundedNulls($this->ContinuousData->HeartRate);
        // TSC: On old Mapjack altitudes sometimes the first value is NULL. Correct it.
        $this->ContinuousDataAdapter->correctArraySurroundedNulls($this->ContinuousData->Altitude);
        // TSC: On Fenix 6 sometimes "Running Dynamics" values have NULL as array elements - set to 0
        $this->ContinuousDataAdapter->correctArrayNullElements($this->ContinuousData->GroundContactTime);
        $this->ContinuousDataAdapter->correctArrayNullElements($this->ContinuousData->VerticalOscillation);
        $this->ContinuousDataAdapter->correctArrayNullElements($this->ContinuousData->GroundContactBalance);
        // TSC: On Fenix swimming
        $this->ContinuousDataAdapter->correctArrayNullElements($this->ContinuousData->Strokes);
        $this->ContinuousDataAdapter->correctArrayNullElements($this->ContinuousData->StrokeType);

        $this->ContinuousDataAdapter->correctArrayNullElements($this->ContinuousData->Cadence);

        $this->ContinuousDataAdapter->correctArrayNullElements($this->ContinuousData->Temperature);

        $this->completeRoundsIfRequired();
        $this->clearRoundsIfOnlyOneRoundIsThere();
        $this->applyPauses();
    }

    public function completeActivityData()
    {
        $this->ActivityData->completeFromContinuousData($this->ContinuousData);
        $this->ActivityData->completeFromRounds($this->Rounds);
        $this->ActivityData->completeFromPauses($this->Pauses);

        // #TSC: set average-temp from the clock/FIT data if available
        if(!empty(($this->ActivityData->AvgTemperature))) {
            // first use the session attribute
            $this->WeatherData->Temperature = $this->ActivityData->AvgTemperature;
        } else {
            // second calc the avg from the temp's
            $this->WeatherData->Temperature = $this->ContinuousDataAdapter->getAverageTemperatur();
        }
    }

    public function filterActivityData(FilterCollection $filter)
    {
        $filter->filter($this);
    }

    protected function completeRoundsIfRequired()
    {
        if (!$this->Rounds->isEmpty() && !empty($this->ContinuousData->Time) && !empty($this->ContinuousData->Distance)) {
            if ($this->Rounds->getTotalDuration() == 0) {
                (new RoundCollectionFiller($this->Rounds))->fillTimesFromArray(
                    $this->ContinuousData->Time,
                    $this->ContinuousData->Distance
                );
            } elseif ($this->Rounds->getTotalDistance() == 0.0) {
                (new RoundCollectionFiller($this->Rounds))->fillDistancesFromArray(
                    $this->ContinuousData->Time,
                    $this->ContinuousData->Distance
                );
            }


            // #TSC check if 'run*' rounds includes intervals
            if (strpos($this->Metadata->getSportName(), 'run') === 0 && $this->Rounds->hasIntervalRounds()) {
                $this->Metadata->setTypeName('Intervalltraining');
            }
        }
    }

    protected function clearRoundsIfOnlyOneRoundIsThere()
    {
        if (!$this->Rounds->isEmpty() && $this->Rounds->count() == 1) {
            $this->Rounds->clear();
        }
    }

    protected function applyPauses()
    {
        if (!$this->PausesToApply->isEmpty() && !empty($this->ContinuousData->Time)) {
            $this->Pauses = $this->ContinuousDataAdapter->applyPauses($this->PausesToApply);
            $this->PausesToApply->clear();

            $this->ContinuousDataAdapter->reIndexArrays();
        }
    }
}
