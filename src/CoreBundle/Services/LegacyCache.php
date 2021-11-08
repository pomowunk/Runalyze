<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Training;

class LegacyCache
{
	/** @var \phpFastCache */
    public static $cache;

    /**
     * @param string $path
     */
	public function __construct($path)
    {
		\phpFastCache::setup("storage", "files");
        \phpFastCache::setup("path", $path);
        \phpFastCache::setup("securityKey", "cache");

		self::$cache = new \phpFastCache;
	}

    /**
     * @param string $keyword
     * @param int|bool $accountId
     * @return bool
     */
	public function delete($keyword, $accountId = false) {
	    $accountId = false === $accountId ? '' : (string)$accountId;

	    if (self::$cache->isExisting($keyword.(string)$accountId)) {
            return self::$cache->delete($keyword.(string)$accountId);
        }

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
