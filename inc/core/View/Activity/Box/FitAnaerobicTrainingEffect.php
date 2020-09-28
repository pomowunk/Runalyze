<?php

namespace Runalyze\View\Activity\Box;

use Runalyze\Activity;
use Runalyze\View\Activity\Context;

// #TSC: Box class used in the statistic heart-rate page to show the anaerobic value
class FitAnaerobicTrainingEffect extends ValueBox
{
	public function __construct(Context $Context)
	{
		parent::__construct(
			new Activity\AnaerobicTrainingEffect($Context->activity()->fitAnaerobicTrainingEffect()),
            '',
            'training-effect' // #TSC: use the same glossary as aerob-training
		);
	}
}
