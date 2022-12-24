<?php

namespace Runalyze\Service\RouteNameEvaluation;

/**
 * Creates a RouteNameEvaluation implementations.
 * Currently there is only the OSM implementation available.
 * #TSC
 */
class RouteNameEvalFactory {
    protected $RouteNameEval;

    /**
     * @param a implementation
     */
    public function __construct(RouteNameEvalOsm $routeNameEvalOsm) {
        $this->RouteNameEval = $routeNameEvalOsm;
    }

    /**
     * Gets a instance.
     * 
     * @return Runalyze\Service\RouteNameEvaluation\RouteNameEval
     */
    public function getInstance(): RouteNameEval {
        return $this->RouteNameEval;
    }
}