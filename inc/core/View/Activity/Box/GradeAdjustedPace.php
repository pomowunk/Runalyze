<?php
/**
 * This file contains class::GradeAdjustedPace
 * @package Runalyze\View\Activity\Box
 */

namespace Runalyze\View\Activity\Box;

use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\Profile\Sport\SportProfile;

/**
 * Boxed value for the grade adjusted pace (=GAP)
 * 
 * @author codeproducer
 * @package Runalyze\View\Activity\Box
 */
class GradeAdjustedPace extends AbstractBox
{
	/**
	 * Constructor
	 * @param \Runalyze\View\Activity\Context $context
	 */
	public function __construct(\Runalyze\View\Activity\Context $context)
	{
		$Pace = $context->dataview()->GApace($context);

		parent::__construct(
			$Pace->value(),
			$Pace->appendix(),
			__('avg.').' '.($Pace->unit()->isTimeFormat() ? 'GAP' : 'GAP Speed')
		);
	}

    public static function hasPace(\Runalyze\View\Activity\Context $context) {
        return $context->sport()->getInternalProfileEnum() == SportProfile::RUNNING
            && $context->hasRoute() && $context->route()->hasElevations()
            && $context->hasTrackdata() && $context->trackdata()->has(Trackdata::DISTANCE);
    }
}