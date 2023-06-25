<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use App\Entity\Hrv;
use App\Entity\Raceresult;
use App\Entity\Route;
use App\Entity\Sport;
use App\Entity\Swimdata;
use App\Entity\Trackdata;
use App\Entity\Training;

class ActivityContext
{
    /** @var Training */
    protected $Activity;

    /** @var Trackdata|null */
    protected $Trackdata = null;

    /** @var Swimdata|null */
    protected $Swimdata = null;

    /** @var Route|null */
    protected $Route = null;

    /** @var Hrv|null */
    protected $HRV = null;

    /** @var Raceresult|null */
    protected $RaceResult = null;

    /** @var ActivityDecorator */
    protected $Decorator;

    public function __construct(
        Training $activity,
        Trackdata $trackdata = null,
        Swimdata $swimdata = null,
        Route $route = null,
        Hrv $hrv = null,
        Raceresult $raceResult = null
    )
    {
        $this->Activity = $activity;
        $this->Trackdata = $trackdata;
        $this->Swimdata = $swimdata;
        $this->Route = $route;
        $this->HRV = $hrv;
        $this->RaceResult = $raceResult;

        $this->Decorator = new ActivityDecorator($this);
    }

    /**
     * @return \App\Entity\Account
     */
    public function getAccount()
    {
        return $this->Activity->getAccount();
    }

    /**
     * @return Training
     */
    public function getActivity()
    {
        return $this->Activity;
    }

    /**
     * @return Trackdata|null
     */
    public function getTrackdata()
    {
        return $this->Trackdata;
    }

    /**
     * @return Swimdata|null
     */
    public function getSwimdata()
    {
        return $this->Swimdata;
    }

    /**
     * @return Hrv|null
     */
    public function getHrv()
    {
        return $this->HRV;
    }

    /**
     * @return Sport
     */
    public function getSport()
    {
        return $this->Activity->getSport();
    }

    /**
     * @return Route|null
     */
    public function getRoute()
    {
        return $this->Route;
    }

    /**
     * @return Raceresult|null
     */
    public function getRaceResult()
    {
        return $this->RaceResult;
    }

    /**
     * @return bool
     */
    public function hasTrackdata() {
        return null !== $this->Trackdata;
    }

    /**
     * @return bool
     */
    public function hasRoute()
    {
        return null !== $this->Route;
    }

    /**
     * @return bool
     */
    public function hasSwimdata()
    {
        return null !== $this->Swimdata;
    }

    /**
     * @return bool
     */
    public function hasHRV()
    {
        return null !== $this->HRV;
    }

    /**
     * @return bool
     */
    public function hasRaceResult()
    {
        return null !== $this->RaceResult;
    }

    /**
     * @return ActivityDecorator
     */
    public function getDecorator()
    {
        return $this->Decorator;
    }

    /**
     * @return boolean
     */
    public function canShowMap()
    {
        return $this->hasRoute() && $this->Route->hasGeohashes();
    }
}
