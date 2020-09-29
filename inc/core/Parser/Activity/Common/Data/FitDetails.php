<?php

namespace Runalyze\Parser\Activity\Common\Data;

class FitDetails
{
    /** @var float|null [ml/kg/min] */
    public $VO2maxEstimate = null;

    /** @var int|null [min] */
    public $RecoveryTime = null;

    /** @var int|null */
    public $HrvAnalysis = null;

    /** @var float|null [0.0 .. 5.0] */
    public $TrainingEffect = null;

    /** @var float|null [0.0 .. 5.0] */
    public $AnaerobicTrainingEffect = null; // #TSC: new anaerob

    /** @var int|null */
    public $PerformanceCondition = null;

    /** @var int|null */
    public $PerformanceConditionEnd = null;

    /** @var int|null */
    public $LactateThresholdHR = null;

    /** @var int|null */
    public $TotalAscent = null;

    /** @var int|null */
    public $TotalDescent = null;
}
