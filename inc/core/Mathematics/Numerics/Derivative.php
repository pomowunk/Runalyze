<?php

namespace Runalyze\Mathematics\Numerics;

use InvalidArgumentException;

/**
 * Calculate array-wise derivative of f(x) = y
 *
 * The derivative of f(x) given at discrete points x_i is calculated by
 *      d/dx f(x_i) = (f(x_i) - f(x_i-1)) / (x_i - x_i-1)
 * for i > 0 and
 *      d/dx f(x_0) = d/dx f(x_1)
 */
class Derivative
{
    // 1000% means 85° grade
    protected $Filter = 1000.0;

    /**
     * @param array $y
     * @param array $x
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function calculate(array $y, array $x)
    {
        if (count($x) !== $num = count($y)) {
            throw new InvalidArgumentException('Input arrays must be of same size.');
        }

        if (0 === $num) {
            throw new InvalidArgumentException('Input arrays must not be empty.');
        }

        $ddx = [];

        for ($i = 1; $i < $num; ++$i) {
            $deltaX = $x[$i] - $x[$i - 1];
            $deltaY = $y[$i] - $y[$i - 1];

            if ($deltaX > 0) {
                $v = $deltaY / $deltaX;
                // #TSC if we have more than +/-X, limit it - it's data bullshit and not possible!!!
                if ($v > $this->Filter) {
                    $v = $this->Filter;
                } elseif ($v < -$this->Filter) {
                    $v = -$this->Filter;
                }
                $ddx[] = $v;
            } elseif ($i > 1) {
                $ddx[] = $ddx[$i - 2];
            } else {
                $ddx[] = 0;
            }
        }

        array_unshift($ddx, $ddx[0]);

        return $ddx;
    }
}
