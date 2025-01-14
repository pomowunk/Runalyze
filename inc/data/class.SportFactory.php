<?php
/**
 * This file contains class::SportFactory
 * @package Runalyze\Data\Sport
 */

use Runalyze\Configuration;
use Runalyze\Activity\Pace;

/**
 * @deprecated
 */
class SportFactory {
	/**
	 * All sports as array
	 * @var array
	 */
	private static $AllSports = null;

	/**
	 * Data for ID
	 * @param int $id sportid
	 * @return array
	 */
	public static function DataFor($id) {
		$Sports = self::AllSports();

		if (isset($Sports[$id]))
			return $Sports[$id];

		return self::defaultArray();
	}

	/**
	 * Array with default values
	 *
	 * @todo This method should be useless as soon as a DatabaseScheme is used
	 * @return array
	 */
	private static function defaultArray() {
		return array(
			'name' => '?',
			'img' => '',
			'short' => 0,
			'kcal' => 600,
			'HFavg' => 140,
			'distances' => 0,
			'speed' => \Runalyze\Metrics\Velocity\Unit\PaceEnum::KILOMETER_PER_HOUR,
			'power'	=> 0,
			'outside' => 0,
            'is_main' => 0,
            'internal_sport_id' => null
		);
	}

	/**
	 * Get all sports
	 * @return array
	 */
	public static function AllSports() {
		if (is_null(self::$AllSports)) {
			self::initAllSports();
		}

		return self::$AllSports;
	}

	/**
	 * Initialize internal sports-array from database
	 */
	private static function initAllSports() {
		self::$AllSports = array();
		$sports = self::cacheAllSports();

		foreach ($sports as $sport) {
			self::$AllSports[(string)$sport['id']] = $sport;
		}

		Configuration::ActivityForm()->orderSports()->sort(self::$AllSports);
	}

	/**
	 * Cache all sports for user
	 */
	private static function cacheAllSports() {
		$sports = Cache::get('sport');

		if (is_null($sports)) {
			$sports = DB::getInstance()->query('SELECT * FROM `'.PREFIX.'sport` WHERE `accountid` = "'.(int)SessionAccountHandler::getId().'"')->fetchAll();
			Cache::set('sport', $sports, 3600);
		}

		return $sports;
	}

	/**
	 * Reinit all sports
	 *
	 * Use this method after updating sports table
	 */
	public static function reInitAllSports() {
		Cache::delete('sport');

		self::initAllSports();
	}

	/**
	 * Get array with all names
	 * @return array ids as keys, names as values
	 */
	public static function NamesAsArray() {
		$sports = self::AllSports();

		foreach ($sports as $id => $sport) {
			$sports[$id] = $sport['name'];
		}

		return $sports;
	}

	/**
	 * Name of sport
	 * @param int $sportid
	 * @return string
	 */
	public static function name($sportid) {
		$Sports = self::AllSports();

		if (isset($Sports[$sportid])) {
			return $Sports[$sportid]['name'];
		}

		return __('unknown');
	}

	/**
	 * Get speed unit for given sportid
	 * @param int $ID
	 * @return int
	 */
	public static function getSpeedUnitFor($ID) {
		$Sports = self::AllSports();

		return (isset($Sports[$ID])) ? (new \Runalyze\Metrics\LegacyUnitConverter())->getLegacyPaceUnit($Sports[$ID]['speed'], true) : Pace::STANDARD;
	}
}
