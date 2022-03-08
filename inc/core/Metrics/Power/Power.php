<?php

namespace Runalyze\Metrics\Power;

use Runalyze\Metrics\Common\AbstractMetric;
use Runalyze\Metrics\Power\Unit\Watts;

class Power extends AbstractMetric
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getBaseUnitClass()
    {
        return Watts::class;
    }
}
