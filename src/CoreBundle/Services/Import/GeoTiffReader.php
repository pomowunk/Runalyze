<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\DEM\Interpolation\BilinearInterpolation;
use Runalyze\DEM\Provider\GeoTIFF\SRTM4Provider;
use Runalyze\DEM\Reader;

class GeoTiffReader extends Reader
{
    /**
     * @param string $srtmDirectory
     */
    public function __construct($srtmDirectory)
    {
        parent::__construct(
            new SRTM4Provider($srtmDirectory, new BilinearInterpolation())
        );
    }
}
