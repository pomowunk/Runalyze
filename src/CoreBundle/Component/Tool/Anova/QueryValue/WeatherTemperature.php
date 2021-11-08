<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Cadence\Unit\AbstractCadenceUnit;
use Runalyze\Metrics\Common\UnitInterface;

class WeatherTemperature extends AbstractOneColumnValue
{
    protected function getColumn()
    {
        return 'temperature';
    }

    /**
     * @param UnitSystem $unitSystem
     * @return AbstractCadenceUnit|UnitInterface
     */
    public function getValueUnit(UnitSystem $unitSystem)
    {
        return $unitSystem->getTemperatureUnit();
    }
}
