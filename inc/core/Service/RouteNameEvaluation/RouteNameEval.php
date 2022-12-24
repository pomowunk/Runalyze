<?php

namespace Runalyze\Service\RouteNameEvaluation;

use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;

/**
 * Interface for retrieving route details from a external service.
 * #TSC 
 */
interface RouteNameEval {

    /**
     * Retrieves the route-name and some other relevant informations of the route.
     * 
     * @param Sport $sport
     * @param Route $route
     * @param distance
     * @return null|\Runalyze\Service\RouteNameEvaluation\RouteNameEvalResult
	 * @throws Exception
     */
    public function evaluate(Sport $sport, Route $route, float $distance): ?RouteNameEvalResult;

}