<?php
/**
 * This file contains class::Object
 * @package Runalyze\Model\EquipmentType
 */

namespace Runalyze\Model\EquipmentType;

use Runalyze\Model;

/**
 * Type object
 * 
 * @author Hannes Christiansen
 * @package Runalyze\Model\EquipmentType
 */
class Object extends Model\ObjectWithID {
	/**
	 * Key: name
	 * @var string
	 */
	const NAME = 'name';

	/**
	 * Key: input
	 * @var string
	 */
	const INPUT = 'input';

	/**
	 * Key: max distance
	 * @var string
	 */
	const MAX_KM = 'max_km';

	/**
	 * Key: max duration
	 * @var string
	 */
	const MAX_TIME = 'max_time';

	/**
	 * All properties
	 * @return array
	 */
	static public function allDatabaseProperties() {
		return array(
			self::NAME,
			self::INPUT,
			self::MAX_KM,
			self::MAX_TIME
		);
	}

	/**
	 * Properties
	 * @return array
	 */
	public function properties() {
		return static::allDatabaseProperties();
	}

	/**
	 * Synchronize
	 */
	public function synchronize() {
		parent::synchronize();

		$this->ensureAllNumericValues();
	}

	/**
	 * Ensure that numeric fields get numeric values
	 */
	protected function ensureAllNumericValues() {
		$this->ensureNumericValue(array(
			self::INPUT,
			self::MAX_KM,
			self::MAX_TIME
		));
	}

	/**
	 * Name
	 * @return string
	 */
	public function name() {
		return $this->Data[self::NAME];
	}

	/**
	 * Allows multiple values
	 * @return boolean
	 */
	public function allowsMultipleValues() {
		return ($this->Data[self::INPUT] == 1);
	}

	/**
	 * Maximal distance
	 * @return int [km]
	 */
	public function maxDistance() {
		return $this->Data[self::MAX_KM];
	}

	/**
	 * Is a maximal distance set?
	 * @return boolean
	 */
	public function hasMaxDistance() {
		return ($this->Data[self::MAX_KM] > 0);
	}

	/**
	 * Maximal duration
	 * @return int [s]
	 */
	public function maxDuration() {
		return $this->Data[self::MAX_TIME];
	}

	/**
	 * Is a maximal duration set?
	 * @return boolean
	 */
	public function hasMaxDuration() {
		return ($this->Data[self::MAX_TIME] > 0);
	}
}