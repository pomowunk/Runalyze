<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use App\Entity\Raceresult;
use App\Entity\Training;
use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Files\Config;

class LegacyCache
{
	/** @var \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface */
    public static $cache;

	public function __construct(string $legacyCachePath)
    {
		$config = new Config();
		$config->setPath($legacyCachePath);
		$config->setSecurityKey('cache');
        $config->setItemDetailedDate(true);
		self::$cache = CacheManager::Files($config);
	}

    /**
     * @param string $keyword
     * @param int|bool $accountId
     * @return bool
     */
	public function delete($keyword, $accountId = false) {
	    $accountId = false === $accountId ? '' : (string)$accountId;

        self::$cache->deleteItem($keyword . (string)($accountId ?: ''));

        return false;
	}

	public function clearActivityCache(Training $activity)
    {
        $accountId = $activity->getAccount()->getId();

        if (null !== $activity->getRoute()) {
            $this->delete('route'.$activity->getRoute()->getId(), $accountId);
        }

        if (null !== $activity->getTrackdata()) {
            $this->delete('trackdata'.$activity->getId(), $accountId);
        }

        if (null !== $activity->getSwimdata()) {
            $this->delete('swimdata'.$activity->getId(), $accountId);
        }

        if (null !== $activity->getHrv()) {
            $this->delete('hrv'.$activity->getId(), $accountId);
        }

        if (null !== $activity->getRaceresult()) {
            $this->delete('raceresult'.$activity->getId(), $accountId);
        }

        $this->delete('training'.$activity->getId(), $accountId);
    }

    public function clearRaceResultCache(Raceresult $raceResult)
    {
        $accountId = $raceResult->getAccount()->getId();

        $this->delete('raceresult'.$raceResult->getActivity()->getId(), $accountId);
    }
}
