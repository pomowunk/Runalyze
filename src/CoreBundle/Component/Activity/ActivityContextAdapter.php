<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;
use Runalyze\Bundle\CoreBundle\Services\Import\WeatherDataToActivityConverter;
use Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Util\LocalTime;

class ActivityContextAdapter
{
    /** @var ActivityContext */
    protected $Context;

    /** @var WeatherForecast */
    protected $WeatherForecast;

    /** @var DuplicateFinder */
    protected $DuplicateFinder;

    public function __construct(
        ActivityContext $context,
        WeatherForecast $weatherForecast,
        DuplicateFinder $duplicateFinder
    )
    {
        $this->Context = $context;
        $this->WeatherForecast = $weatherForecast;
        $this->DuplicateFinder = $duplicateFinder;
    }

    /**
     * @param object $object
     * @return string
     */
    protected function getStrategyName($object)
    {
        $fullClassName = get_class($object);

        return substr($fullClassName, strrpos($fullClassName, '\\')+1);
    }

    /**
     * @param string $defaultLocationName
     */
    public function guessWeatherConditions($defaultLocationName, $account)
    {
        $location = new Location();
        $location->setLocationName($defaultLocationName);

        // #TSC set the time to the mid of the activity
        $time = new LocalTime($this->Context->getActivity()->getTime());
        // we add here the elapsed time, not the duration; half this time to set the mid of the activity
        $time->add(new \DateInterval('PT'. round($this->Context->getActivity()->getElapsedTime() / 2 ).'S'));
        $location->setDateTime($time);

        if ($this->Context->hasRoute() && $this->Context->getRoute()->hasGeohashes()) {
            $this->Context->getRoute()->setStartEndGeohashes();

            $location->setGeohash($this->Context->getRoute()->getStartpoint());
        }

        // #TSC set also the timezone for fetch historical time based data
        $timezone = (int)$account->getTimezone();
        $timezoneName = Timezone::getFullNameByEnum($timezone);
        $location->setTimezone($timezoneName);

        $weather = $this->WeatherForecast->loadForecast($location);

        if (null !== $weather) {
            $converter = new WeatherDataToActivityConverter();
            $converter->setActivityWeatherDataFor($this->Context->getActivity(), $weather);
        }
    }

    /**
     * @return bool
     */
    public function isPossibleDuplicate()
    {
        return $this->DuplicateFinder->isPossibleDuplicate($this->Context->getActivity());
    }
}
