<?php
/**
 * This file contains class::Entity
 * @package Runalyze\Model\Swimdata
 */

namespace Runalyze\Model\Swimdata;

use Runalyze\Model;
use Runalyze\Model\Trackdata;

/**
 * Swimdata entity
 *
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\Model\Swimdata
 */
class Entity extends Model\Entity implements Model\Loopable {
	/**
	 * Key: activity id
	 * @var string
	 */
	const ACTIVITYID = 'activityid';

	/**
	 * Key: stroke
	 * @var string
	 */
	const STROKE = 'stroke';

	/**
	 * Key: pool length
	 * @var string
	 */
	const POOL_LENGTH = 'pool_length';

	/**
	 * Key: stroketype
	 * @var string
	 */
	const STROKETYPE = 'stroketype';

	/**
	 * Key: swolf
	 * @var string
	 */
	const SWOLF = 'swolf';

	/**
	 * Key: SWOLFCYCLES
	 * @var string
	 */
	const SWOLFCYCLES = 'swolfcycles';

	/**
	 * Construct
	 * @param array $data
	 */
	public function __construct(array $data = array()) {
		parent::__construct($data);
		$this->checkArraySizes();
	}

	/**
	 * All properties
	 * @return array
	 */
	public static function allProperties() {
		return array(
			self::ACTIVITYID,
			self::STROKE,
			self::STROKETYPE,
			self::SWOLF,
			self::SWOLFCYCLES,
			self::POOL_LENGTH
		);
	}

	/**
	 * Can be null?
	 * @param string $key
	 * @return boolean
	 */
	protected function canBeNull($key) {
		switch ($key) {
			case self::STROKE:
			case self::STROKETYPE:
			case self::SWOLF:
			case self::SWOLFCYCLES:
				return true;
		}

		return false;
	}

	/**
	 * Is the property an array?
	 * @param string $key
	 * @return bool
	 */
	public function isArray($key) {
		return ($key != self::ACTIVITYID && $key != self::POOL_LENGTH);
	}

	/**
	 * Properties
	 * @return array
	 */
	public function properties() {
		return static::allProperties();
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
			self::POOL_LENGTH
		));
	}

	/**
	 * Number of points
	 * @return int
	 */
	public function num() {
		return $this->numberOfPoints;
	}

	/**
	 * Value at
	 *
	 * Remark: This method may throw index offsets.
	 * @param int $index
	 * @param string $key
	 * @return mixed
	 */
	public function at($index, $key) {
		return $this->Data[$key][$index];
	}

	/**
	 * Activity ID
	 * @return int
	 */
	public function activityID() {
		return $this->Data[self::ACTIVITYID];
	}

	/**
	 * STROKE
	 * @return array
	 */
	public function stroke() {
		return $this->Data[self::STROKE];
	}

	/**
	 * STROKETYPE
	 * @return array
	 */
	public function stroketype() {
		return $this->Data[self::STROKETYPE];
	}

	/**
	 * SWOLF
	 * @return array
	 */
	public function swolf() {
		return $this->Data[self::SWOLF];
	}

	/**
	 * SWOLFcycles
	 * @return array
	 */
	public function swolfcycles() {
		return $this->Data[self::SWOLFCYCLES];
	}

	/**
	 * STROKETYPE
	 * @return int [cm]
	 */
	public function poollength() {
		return $this->Data[self::POOL_LENGTH];
	}

	/*
	 * Calculate Distance based on pool length
	 */
	public function fillDistanceArray(Trackdata\Entity $trackdata) {
		if ($this->poollength() && !$trackdata->has(Trackdata\Entity::DISTANCE) && $this->num() == $trackdata->num()) {
			$length = $this->poollength();

			$distance = null;
			if($this->stroke() && $this->num() == count($this->stroke())) {
				// #TSC: optimise for GARMINs: if we have 0 strokes for one lane, distance must be 0 for this lane; because it's a erholung/"length_type=0=idle" lane
				$d = 0;
				foreach ($this->stroke() as &$str) {
					// only add pool-length if strokes are done in this lane
					$d = $d + ($str > 0 ? $length : 0);
					$distance[] = $d;
				}
			} else {
				// if we have no strokes, fill the array as before; every lane = pool-length
				$distance = range($length, $this->num()*$length, $length);
			}
			// from meter to kilometer
			$distance = array_map(function($v) { return $v / 100000; }, $distance);
			$trackdata->set(Trackdata\Entity::DISTANCE, $distance);
			$trackdata->calculatePaceArray();
		}
	}

	/*
	 * Create swolf array
	 * http://marathonswimmers.org/blog/2012/05/stroke-count-games/
	 */
	public function fillSwolfArray(Trackdata\Entity $trackdata) {
		if ($this->stroke() && $trackdata->has(Trackdata\Entity::TIME) && $this->num() == $trackdata->num()) {
			$TrackLoop = new Trackdata\Loop($trackdata);
			$Loop = new Loop($this);

			$max = $Loop->num();
			$swolf = array();
			$swolfcycles = array();

			for ($i = 1; $i <= $max; ++$i) {
				$duration = $TrackLoop->difference(Trackdata\Entity::TIME);

				// #TSC if we have no stroke for this lane we cant calulate SWOLF
				if ($Loop->stroke() > 0) {
					$swolf[] = $duration + $Loop->stroke();
					$swolfcycles[] = $duration + $Loop->stroke()/2;
				} else {
					$swolf[] = null;
					$swolfcycles[] = null;
				}

				$Loop->nextStep();
				$TrackLoop->nextStep();
			}

			$this->set(Entity::SWOLF, $swolf);
			$this->set(Entity::SWOLFCYCLES, $swolfcycles);
		}
	}
}
