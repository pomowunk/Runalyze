<?php

namespace App\Tests\Entity;

use App\Entity\Swimdata;
use PHPUnit\Framework\TestCase;

class SwimdataTest extends TestCase
{
    /** @var Swimdata */
    protected $Data;

    public function setUp(): void
    {
        $this->Data = new Swimdata();
    }

    public function testEmptyEntity()
    {
        $this->assertTrue($this->Data->isEmpty());
        $this->assertNull($this->Data->getStroke());
        $this->assertNull($this->Data->getStroketype());
    }
}
