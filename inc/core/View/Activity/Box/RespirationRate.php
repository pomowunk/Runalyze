<?php

namespace Runalyze\View\Activity\Box;

use Runalyze\View\Activity\Context;

class RespirationRate extends AbstractBox
{
    public function __construct(Context $context)
    {
        parent::__construct(
            $context->activity()->avgRespirationRate(),
            'brpm',
            '&empty; Atemfrequenz',
            '',
            'respiration'
        );
    }
}
