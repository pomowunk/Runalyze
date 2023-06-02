<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal\Service;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Profile\Weather\Source\SourceInterface;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Service\WeatherForecast\Location;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/_internal/service/weather")
 */
class WeatherController extends Controller
{
    /**
     * @Route("", name="internal-service-weather")
     * @Security("has_role('ROLE_USER')")
     */
    public function fetchWeatherDataAction(
        Request $request,
        Account $account,
        WeatherForecast $weatherForecast,
        ConfigurationManager $configurationManager,
        TranslatorInterface $translator)
    {
        $location = $this->getLocationForRequest($request, $account, $configurationManager);

        $weather = $weatherForecast->loadForecast($location) ?: new WeatherData();

        /** @var SourceInterface|null $source */
        $source = $weather->Source ? WeatherSourceProfile::get($weather->Source) : null;

        return new JsonResponse([
            'empty' => $weather->isEmpty(),
            'location' => [
                'name' => $location->getLocationName(),
                'lat' => $location->hasPosition() ? $location->getLatitude() : '',
                'lng' => $location->hasPosition() ? $location->getLongitude() : '',
                'date' => $location->hasDateTime() ? $location->getDateTime()->format('c') : ''
            ],
            'source' => [
                'id' => $weather->Source,
                'name' => null !== $source ? $source->getAttribution($translator) : ''
            ],
            'weatherid' => $weather->InternalConditionId,
            'temperature' => $weather->Temperature,
            'wind_speed' => $weather->WindSpeed,
            'wind_deg' => $weather->WindDirection,
            'humidity' => $weather->Humidity,
            'pressure' => $weather->AirPressure
        ]);
    }

    /**
     * @param Request $request
     * @param Account $account
     * @return Location
     */
    private function getLocationForRequest(
        Request $request,
        Account $account,
        ConfigurationManager $configurationManager)
    {
        $location = new Location();

        if ($request->query->has('geohash')) {
            $location->setGeohash($request->query->get('geohash'));
        } elseif ($request->query->has('latlng')) {
            $latLng = explode(',', $request->query->get('latlng'));

            if (2 == count($latLng)) {
                $location->setPosition((float)$latLng[0], (float)$latLng[1]);
            }
        }

        if ($request->query->has('city')) {
            $location->setLocationName($request->query->get('city'));
        } elseif (!$location->hasPosition()) {
            $location->setLocationName($configurationManager->getList($account)->get('activity-form.PLZ'));
        }

        if ($request->query->has('time')) {
            $location->setDateTime(new \DateTime('@'.$request->query->get('time')));
        } elseif ($request->query->has('date')) {
            $location->setDateTime(new \DateTime($request->query->get('date')));
        }

        return $location;
    }
}
