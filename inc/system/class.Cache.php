<?php

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Files\Config;

/**
 * Class: Cache - Wrapper for PHPFastCache
 * @author Michael Pohl
 * @package Runalyze\System
 */

class Cache {
	/**
	 * Path for cache, relative to runalyze root
	 * @var string
	 */
	const PATH = 'data';

	/**
	 * Last cache clean date
	 * @var int
	*/
	private static $LASTCLEAN = null;

    /**
     * ignore some keywords
     */
    private static $ignoreKeywords = ['sport'];

	/**
	 * Boolean flag: Cache enabled?
	 * @var bool
	 */
	public $footer_sent = true;

	/** @var \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface */
    public static $cache;

	/**
	 * Prohibit creating an object from outside
	 */
	public function __construct() {
		$config = new Config();
		$config->setPath(FRONTEND_PATH."../".self::PATH);
		$config->setSecurityKey('cache');
        $config->setItemDetailedDate(true);
		self::$cache = CacheManager::Files($config);
	}

	/**
	 * Set Cache
	 * @param int $time
	 */
	public static function set($keyword, $data, $time, $nousercache = 0) {
		$key = $keyword;
		if($nousercache == 0) { 
			$key .= (string)SessionAccountHandler::getId();
		}
		$cacheItem = self::$cache->getItem($key);
		$cacheItem->set($data);
		$cacheItem->expiresAfter($time);
		self::$cache->save($cacheItem);
	}

	/**
	 * Get Cache
	 */
	public static function get($keyword, $nousercache = 0) {
		if ($nousercache == 0 && !in_array($keyword, self::$ignoreKeywords)) {
            $cacheItem = self::$cache->getItem($keyword . SessionAccountHandler::getId());
			$lastcacheclean = self::$LASTCLEAN;
			if ($lastcacheclean === null) {
				$lastcacheclean = self::$cache->getItem('LASTCLEAN' . SessionAccountHandler::getId())->get();
				$lastcacheclean = $lastcacheclean ?: 0;
				self::$LASTCLEAN = $lastcacheclean;
			}
			if ($cacheItem->getModificationDate()->getTimestamp() > $lastcacheclean) {
				return $cacheItem->get();
			} else {
				return null;
			}
		} else {
			return self::$cache->getItem($keyword)->get();
		}
	}

	/**
	 * Delete from cache
	 */
	public static function delete($keyword, $nousercache = 0) {
	    if (!in_array($keyword, self::$ignoreKeywords)) {
			$key = $keyword;
			if($nousercache == 0) { 
				$key .= (string)SessionAccountHandler::getId();
			}
			self::$cache->deleteItem($keyword);
		}
	}

	/**
	 * Clean up all cache
	 */
	public static function clean() {
		self::$LASTCLEAN = time();

		if (SessionAccountHandler::getId() === null) {
			self::$cache->clear();
		} else {
			$cacheItem = self::$cache->getItem('LASTCLEAN' . SessionAccountHandler::getId());
			$cacheItem->set(self::$LASTCLEAN);
			self::$cache->save($cacheItem);
		}
	}

	/**
	 * is existing?
	 */
	public static function is($keyword, $nousercache = 0) {
		$key = $keyword;
		if ($nousercache == 0) { 
			$key .= (string)SessionAccountHandler::getId();
		}
		return self::$cache->getItem($key)->isHit();
	}
}
