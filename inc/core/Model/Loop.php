<?php
/**
 * This file contains class::Loop
 * @package Runalyze\Model
 */

namespace Runalyze\Model;

/**
 * Loop through object
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
abstract class Loop
{
	/**
	 * Current index
	 * @var int
	 */
	protected $Index = 0;

	/**
	 * Last index
	 * @var int
	 */
	protected $LastIndex = 0;

	/**
	 * Step size
	 * @var int
	 */
	protected $StepSize = 1;


	// #TSC
	// is used to identifiy a moveTo or a nextStep is done. this is necessary to identify the FIRST
	// difference call; if it starts from 0-? and build a difference or used the idx directly
	// see difference method
	// example:
	// if we step to the loop with "nextStep" the process is: current gets the first value, do a "nextStep", current gets the second value
	// if "moveTo" is used: first set the index with a "moveTo" and get the current; so the idx-pointer is set before the first value is requested
	// so the moveTo and nextStep increased the counter with different values ;-)
	protected $InitMove = -1;

	/**
	 * Total size
	 * @var int
	 */
	protected $TotalSize;

	/**
	 * Object
	 * @var \Runalyze\Model\Loopable
	 */
	protected $Object;

	/**
	 * Construct
	 * @param \Runalyze\Model\Loopable $object
	 */
	public function __construct(Loopable $object)
	{
		$this->Object = $object;

		$this->countTotalSize();
		$this->reset();
	}

	/**
	 * Count total size
	 */
	protected function countTotalSize()
	{
		$this->TotalSize = $this->Object->num();
	}

	/**
	 * @return int
	 */
	public function num()
	{
		return $this->TotalSize;
	}

	/**
	 * Reset
	 *
	 * Sets the internal pointer to the beginning.
	 * This method does not change the step size.
	 */
	public function reset()
	{
		$this->Index = 0;
		$this->LastIndex = 0;
		$this->InitMove = -1;
	}

	/**
	 * Set step size
	 * @param int $size
	 */
	public function setStepSize($size)
	{
		$this->StepSize = $size;
	}

	/**
	 * Move index forward
	 * @return boolean
	 */
	public function nextStep()
	{
		// #TSC we set here +2 because with the first nextStep call we must calculate a difference
		$this->InitMove = $this->InitMove + 2;

		$this->LastIndex = $this->Index;
		$this->Index += $this->StepSize;

		// #TSC debug
		//echo('# Loop.nextStep to='.$this->Index.' ');

		if ($this->Index > $this->TotalSize - 1) {
			$this->Index = $this->TotalSize - 1;
			return false;
		}

		return true;
	}

	/**
	 * Is at the end?
	 * @return boolean
	 */
	public function isAtEnd()
	{
		return ($this->Index >= $this->TotalSize - 1);
	}

	/**
	 * Move pointer
	 * @param string $key
	 * @param float $value to move
	 * @throws \InvalidArgumentException
	 */
	public function move($key, $value)
	{
		$this->moveTo($key, $this->Index == 0 && $value > $this->current($key) ? $value : $this->current($key) + $value);
	}

	/**
	 * @return int
	 */
	public function index()
	{
		return $this->Index;
	}

	/**
	 * @return int
	 */
	public function lastIndex()
	{
		return $this->LastIndex;
	}

	/**
	 * @return int
	 */
	public function currentStepSize()
	{
		return $this->Index - $this->LastIndex;
	}

	/**
	 * Go to index
	 * @param int $index
	 */
	public function goToIndex($index)
	{
		$this->InitMove++;

		$this->LastIndex = $this->Index;
		$this->Index = $index;
	}

	/**
	 * Go to end
	 */
	public function goToEnd()
	{
		$this->goToIndex($this->TotalSize - 1);
	}

	/**
	 * Move pointer to
	 * @param enum $key
	 * @param float $target to move
	 * @throws \InvalidArgumentException
	 */
	protected function moveTo($key, $target)
	{
		if (!$this->Object->has($key)) {
			throw new \InvalidArgumentException('No array available.');
		}

		$this->LastIndex = $this->Index;

		// called with TIME or DISTANCE as key and search the array for the target value
		// example array: ...569|608|642... and search for target=610 we stop on 642
		while (
			!$this->isAtEnd() &&
			$this->Object->at($this->Index, $key) < $target
		) {
			$this->Index++;
		}

		// #TSC
		// if this is not the value we search for and not the end, decrease the index to point to the correct value
		// the "$this->Index > $this->LastIndex + 1" is necessary to avoid a endless-loop
		// for running this does not play a major role (because of tracking intervals of a second); but for swimming the "time" tracking
		// is based on a swim lane!
		$curr = $this->Object->at($this->Index, $key);
		if($curr != $target && $this->Index > $this->LastIndex + 1 && $this->Index > 0 && !$this->isAtEnd()) {
			// with this step we stop on the below example on "608"
			$this->Index = $this->Index - 1;
		}

		// #TSC: for every step increase the counter
		$this->InitMove++;

		// #TSC debug
		//echo('# Loop.moveTo key='.$key. ' target='.$target.' '.' result='.$this->Object->at($this->Index, $key).' idx='.$this->Index.' lidx='.$this->LastIndex.' <br> ');
	}

	/**
	 * Current value
	 * @param string $key
	 * @return int
	 */
	public function current($key)
	{
		if ($this->Object->has($key)) {
			return $this->Object->at($this->Index, $key);
		}

		return 0;
	}

	/**
	 * Difference for current section
	 * @param string $key
	 * @return float
	 */
	public function difference($key)
	{
		if ($this->Object->has($key)) {
			// #TSC if we do not have done a moveTo, use not a difference rather the raw value
			if (($this->LastIndex == 0 && $this->Index == 0) || $this->InitMove < 1) {
				// #TSC debug
				//echo('# Loop.difference key='.$key.' from='.$this->Object->at($this->Index, $key).' init initMove='.$this->InitMove.' ');

				return $this->Object->at($this->Index, $key);
			}

			$idx = $this->Index;
			$lidx = $this->LastIndex;
			// #TSC: better debuggung
			// echo('# Loop.difference key='.$key.' from='.$this->Object->at($lidx, $key).'-'.$this->Object->at($this->Index, $key).' idx=('.$lidx.','.$idx.') ');

			return $this->Object->at($idx, $key) - $this->Object->at($lidx, $key);
		}

		return 0;
	}

	/**
	 * Sum values for current section
	 * @param string $key
	 * @return float
	 */
	public function sum($key)
	{
		$sum = 0;

		if ($this->Object->has($key)) {
			$start = $this->LastIndex == 0 ? $this->LastIndex : $this->LastIndex + 1;
			for ($i = $start; $i <= $this->Index; ++$i) {
				$sum += $this->Object->at($i, $key);
			}
		}

		return $sum;
	}

	/**
	 * Maximal value for current section
	 * @param string $key
	 * @return float
	 */
	public function max($key)
	{
		$max = -PHP_INT_MAX;

		if ($this->Object->has($key)) {
			$start = $this->LastIndex == 0 ? $this->LastIndex : $this->LastIndex + 1;
			for ($i = $start; $i <= $this->Index; ++$i) {
				if ($this->Object->at($i, $key) > $max) {
					$max = $this->Object->at($i, $key);
				}
			}
		}

		if ($max == -PHP_INT_MAX) {
			return null;
		}

		return $max;
	}

	/**
	 * Average value for current section
	 * @param string $key
	 * @return int
	 */
	public function average($key)
	{
		if ($this->LastIndex >= $this->Index) {
			return 0;
		}

		return $this->sum($key) / ($this->Index - $this->LastIndex + (int)($this->LastIndex == 0));
	}

	/**
	 * @param string $key
	 * @return array
	 */
	public function slice($key)
	{
		if ($this->Object->has($key)) {
			$start = $this->LastIndex == 0 ? $this->LastIndex : $this->LastIndex + 1;
			return array_slice($this->Object->get($key), $start, $this->Index - $start + 1);
		}

		return array();
	}

	/**
	 * Slice object based on last/current index
	 * @return \Runalyze\Model\Entity
	 * @throws \RuntimeException
	 */
	public function sliceObject()
	{
		if ($this->Object instanceof Runalyze\Model\Entity)
		{
			throw new \RuntimeException('This object cannot be sliced.');
		}

		$data = array();

		foreach ($this->Object->properties() as $key)
		{
			if ($this->Object->isArray($key))
			{
				$data[$key] = $this->slice($key);
			}
		}

		return $this->createNewObject($data);
	}

	/**
	 * @param array $data
	 * @return \Runalyze\Model\Entity
	 */
	abstract protected function createNewObject(array $data);
}
