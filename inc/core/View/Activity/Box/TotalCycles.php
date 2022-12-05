<?php

namespace Runalyze\View\Activity\Box;

use Runalyze\View\Activity\Context;

class TotalCycles extends AbstractBox
{
    public function __construct(Context $context)
    {
        parent::__construct(
            $context->activity()->totalCycles(),
            '',
            'Cycles'
        );
    }
}
