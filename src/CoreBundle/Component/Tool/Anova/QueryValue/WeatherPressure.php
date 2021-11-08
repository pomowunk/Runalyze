<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Cadence\Unit\AbstractCadenceUnit;
use Runalyze\Metrics\Common\Unit\Simple;
use Runalyze\Metrics\Common\UnitInterface;

class WeatherPressure extends AbstractOneColumnValue
{
    protected function getColumn()
    {
        return 'pressure';
    }

    /**
     * @param UnitSystem $unitSystem
     * @return AbstractCadenceUnit|UnitInterface
     */
    public function getValueUnit(UnitSystem $unitSystem)
    {
        return new Simple('hpa');
    }
}
