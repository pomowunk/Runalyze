<?php

namespace Runalyze\Parser\Activity\Common\Data\Round;

class RoundCollection implements \Countable, \ArrayAccess, \Iterator
{
    /** @var Round[] */
    protected $Elements = [];

    /** @var int */
    protected $CurrentOffset = 0;

    /**
     * @param Round[] $elements
     */
    public function __construct(array $elements = [])
    {
        foreach ($elements as $offset => $value) {
            $this->offsetSet($offset, $value);
        }
    }

    public function __clone()
    {
        foreach ($this->Elements as $i => $element) {
            $this->Elements[$i] = clone $element;
        }
    }

    public function clear()
    {
        $this->Elements = [];
        $this->CurrentOffset = 0;
    }

    public function add(Round $round)
    {
        $this->Elements[] = $round;
    }

    /**
     * @return Round[]
     */
    public function getElements()
    {
        return $this->Elements;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->Elements);
    }

    public function count()
    {
        return count($this->Elements);
    }

    public function offsetExists($offset)
    {
        return isset($this->Elements[$offset]);
    }

    /**
     * @param int $offset
     * @return Round
     */
    public function offsetGet($offset)
    {
        return $this->Elements[$offset];
    }

    /**
     * @param int $offset
     * @param Round $value
     *
     * @throws \InvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        if (!($value instanceof Round)) {
            throw new \InvalidArgumentException('Round collection does only accept instances of Round as elements.');
        }

        if (null === $offset) {
            $this->Elements[] = $value;
        } else {
            $this->Elements[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->Elements[$offset]);
    }

    public function current()
    {
        return $this->Elements[$this->CurrentOffset];
    }

    public function key()
    {
        return $this->CurrentOffset;
    }

    public function next()
    {
        ++$this->CurrentOffset;
    }

    public function rewind()
    {
        $this->CurrentOffset = 0;
    }

    public function valid()
    {
        return isset($this->Elements[$this->CurrentOffset]);
    }

    /**
     * @return int
     */
    public function getTotalDuration()
    {
        return array_reduce(
            $this->getElements(),
            function ($carry, Round $round) {
                return $carry + $round->getDuration();
            },
            0
        );
    }

    /**
     * @return float
     */
    public function getTotalDistance()
    {
        return array_reduce(
            $this->getElements(),
            function ($carry, Round $round) {
                return $carry + $round->getDistance();
            },
            0.0
        );
    }

    public function roundDurations()
    {
        foreach ($this->Elements as $round) {
            $round->roundDuration();
        }
    }

    /**
     * @return bool
     */
    public function isEqualTo(RoundCollection $other)
    {
        if ($this->count() != $other->count()) {
            return false;
        }

        foreach ($this->Elements as $key => $round) {
            if (!$round->isEqualTo($other->offsetGet($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * #TSC check if the rounds has at least 4 intervals.
     *
     * Example for 4 intervals with 2:30min running and 1:30min recovery: 0=441, 1=150, 2=90, 3=150, 4=90, 5=150, 6=90, 7=150, 8=462 
     */
    public function hasIntervalRounds() {
        $intDetect = 5;
        $precS = 1; // round duration/seconds
        $precD = 2; // round distance/km

        if ($this->count() >= $intDetect) {
            $intCount = 0;

            // iterate over all rounds and check if distance or duration of the next-next is the same value
            for ($i = 0; $i < count($this->Elements) - 2; $i++) {
                // i do ignore the round-active-flag...
                $thisR = $this->Elements[$i];
                $nextR1 = $this->Elements[$i + 1];
                $nextR2 = $this->Elements[$i + 2];
                // first check we not in auto laping (=duration or distance is always the same)
                // second check if the next-next is the same
                if ((round($thisR->getDuration(), $precS) != round($nextR1->getDuration(), $precS) 
                        && round($thisR->getDistance(), $precD) != round($nextR1->getDistance(), $precD)) 
                    && (round($thisR->getDuration(), $precS) == round($nextR2->getDuration(), $precS) 
                        || round($thisR->getDistance(), $precD) == round($nextR2->getDistance(), $precD))
                    ) {
                    $intCount++;
                    if ($intCount == $intDetect) {
                        // if we found 5x consensus we have 4 intervals
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
