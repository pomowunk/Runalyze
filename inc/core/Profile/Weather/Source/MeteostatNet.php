<?php

namespace Runalyze\Profile\Weather\Source;

use Symfony\Component\Translation\TranslatorInterface;

/** #TSC new source of meteostat.net */
class MeteostatNet extends AbstractSource
{
	public function getInternalProfileEnum()
    {
        return WeatherSourceProfile::METEOSTAT_NET;
    }

    public function getAttributionLabel(TranslatorInterface $translator)
    {
        return 'meteostat.net';
    }

    public function getAttributionUrl()
    {
        return 'https://meteostat.net/';
    }
}
