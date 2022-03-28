<?php

namespace Runalyze\Service\WeatherForecast\Strategy;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Profile\Weather\Mapping\MeteostatNetMapping;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Service\WeatherForecast\Location;

/**
 * Weather-strategy for using meteostat.net.
 * meteostat.net is a free cost service for get historical weather data and is now hosted via rapidapi.com.
 * It loads for the GPS lat/lon the hourly (historical) weather-data and fetch the timed data in the mid of the activity.
 * #TSC
 *
 * @see https://meteostat.net / https://dev.meteostat.net/api/point/hourly.html#endpoint
 */
class MeteostatNet implements StrategyInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var string */
    const URL = 'https://meteostat.p.rapidapi.com/point/hourly';

    /** @var string */
    protected $ApiKey;

    /** @var Client */
    protected $HttpClient;

    /**
     * @param string $apiKey
     * @param Client $client
     * @param LoggerInterface|null $logger
     */
    public function __construct($apiKey, Client $client, LoggerInterface $logger = null)
    {
        $this->ApiKey = $apiKey;
        $this->HttpClient = $client;
        $this->logger = $logger;
    }

    public function isPossible()
    {
        return strlen($this->ApiKey) > 0;
    }

    public function isCachable()
    {
        return true;
    }

    public function loadForecast(Location $location)
    {
        $result = $this->tryToLoadForecast($location);

        if (!is_array($result) || empty($result)) {
            return null;
        }

        $this->updateLocationFromResult($location, $result);

        return $this->getWeatherDataFromResult($result);
    }

    /**
     * @param array $currently
     *
     * @return WeatherData
     */
    protected function getWeatherDataFromResult(array $currently)
    {
        $data = new WeatherData();
        $data->Source = WeatherSourceProfile::METEOSTAT_NET;

        if (isset($currently['temp'])) {
            $data->Temperature = (int)round($currently['temp']);
        }

        if (isset($currently['pres'])) {
            $data->AirPressure = (int)round($currently['pres']);
        }

        if (isset($currently['rhum'])) {
            $data->Humidity = (int)round($currently['rhum']);
        }

        if (isset($currently['wspd'])) {
            $data->WindSpeed = (int)round($currently['wspd']);
        }

        if (isset($currently['wdir'])) {
            $data->WindDirection = (int)round($currently['wdir']);
        }

        $data->InternalConditionId = $this->getInternalConditionId($currently);

        return $data;
    }

    /**
     * @param array $result
     *
     * @return int
     */
    protected function getInternalConditionId(array $result)
    {
        if (isset($result['coco'])) {
            $this->logger->debug(sprintf('meteostat.net weather condition %s.', $result['coco']));

            return (new MeteostatNetMapping())->toInternal($result['coco']);
        }

        return WeatherConditionProfile::UNKNOWN;
    }

    /**
     * @param Location $location
     *
     * @return array
     */
    protected function tryToLoadForecast(Location $location)
    {
        $url = $this->getUrlFor($location);
        $this->logger->debug(sprintf('url >> %s.', $url));

        try {

            $response = $this->HttpClient->request('GET', $url, [ 'headers' => [
                'x-rapidapi-host' => 'meteostat.p.rapidapi.com',
                'x-rapidapi-key' => $this->ApiKey ]]);
            $statusCode = $response->getStatusCode();

            $repCont = $response->getBody()->getContents();
            $this->logger->info(sprintf('status-code >> %d result-raw >> %s', $statusCode, $repCont));

            $result = json_decode($repCont, true);

            // we've get a json with 'meta' and 'data'; 'data' contains one object for every hour
            if (is_array($result)) {
                if (isset($result['data']) && !empty($result['data'])) {
                    // retrieve the data-element with our trainings-time
                    return $this->getDataElementOfTime($location->hasDateTime() ? $location->getDateTime() : time(), $result['data']);
                }
            }
        } catch (RequestException $e) {
            $this->logger->warning('MeteostatNet API request failed.', ['exception' => $e]);
        }

        return [];
    }

    protected function getDataElementOfTime($time, $data) {
        // get activity-time in string-format
        $ymdhms = $time->format('Y-m-d H:i:s');
        $this->logger->debug(sprintf('searching data elements with date %s', $ymdhms));

        // the array is sorted from 00:00 to 23:00; do the reverse and select the right
        foreach (array_reverse($data) as &$value) {
            if(isset($value['time']) && $ymdhms >= $value['time']) {
                $this->logger->debug(sprintf('found data elements for time %s >%s<', $ymdhms, print_r($value, true)));
                return $value;
            }
        }

        return [];
    }

    /**
     * @param Location $location
     *
     * @return string
     */
    protected function getUrlFor(Location $location)
    {
        $parameter = [];

        if ($location->hasPosition()) {
            $parameter[] = 'lat='.$location->getLatitude();
            $parameter[] = 'lon='.$location->getLongitude();
        }
        $time = $location->hasDateTime() ? $location->getDateTime() : time();
        $ymd = $time->format('Y-m-d');
        $parameter[] = 'start='.$ymd;
        $parameter[] = 'end='.$ymd;
        $parameter[] = 'tz='.$location->getTimezone();

        return sprintf(
            '%s?%s',
            self::URL,
            implode('&', $parameter)
        );
    }

    protected function updateLocationFromResult(Location $location, array $result)
    {
        if (isset($result['time_local'])) {
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $result['time_local']);

            $location->setDateTime($date);
        }
    }
}
