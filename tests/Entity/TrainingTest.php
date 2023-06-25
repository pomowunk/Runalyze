<?php

namespace App\Tests\Entity;

use App\Entity\Training;
use PHPUnit\Framework\TestCase;

class TrainingTest extends TestCase
{
    /** @var Training */
    protected $Activity;

    public function setUp(): void
    {
        $this->Activity = new Training();
    }

    public function testCloningSplits()
    {
        $oldSplits = $this->Activity->getSplits();
        $this->Activity->setSplitsToClone();

        $this->assertNotSame($this->Activity->getSplits(), $oldSplits);
    }
}
