<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContextAdapter;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalFactory;
use Runalyze\Service\RouteNameEvaluation\RouteNameEval;

class ActivityContextAdapterFactory
{
    /** @var WeatherForecast */
    protected $WeatherForecast;

    /** @var DuplicateFinder */
    protected $DuplicateFinder;

    protected $RouteNameEval;

    public function __construct(
        WeatherForecast $weatherForecast,
        DuplicateFinder $duplicateFinder,
        RouteNameEvalFactory $routeNameEvalFactory)
    {
        $this->WeatherForecast = $weatherForecast;
        $this->DuplicateFinder = $duplicateFinder;
        $this->RouteNameEval = $routeNameEvalFactory->getInstance();
    }

    public function getAdapterFor(ActivityContext $context)
    {
        return new ActivityContextAdapter(
            $context,
            $this->WeatherForecast,
            $this->DuplicateFinder,
            $this->RouteNameEval
        );
    }
}
