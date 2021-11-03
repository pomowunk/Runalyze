<?php

namespace Runalyze\Parameter\Application;

use PHPUnit\Framework\TestCase;

class TimezoneTest extends TestCase
{

    public function testInvalidEnum()
    {
    	$this->expectException(\InvalidArgumentException::class);

        Timezone::getFullNameByEnum(-1);
    }

    public function testThatThereIsAMapping()
    {
        $this->assertNotEmpty(Timezone::getMapping());
    }

    public function testThatMappingIsCompleteAndValid()
    {
        $this->expectNotToPerformAssertions();

        // We can't be sure which timezone database version is used
        $unknownTimezones = [];

        foreach (Timezone::getEnum() as $enum) {
            $identifier = Timezone::getFullNameByEnum($enum);

            try {
                new \DateTimeZone($identifier);
            } catch (\Exception $e) {
                $unknownTimezones[] = $identifier;
            }
        }

        if (!empty($unknownTimezones)) {
            $this->markTestSkipped('Unknown timezones: '.implode(', ', $unknownTimezones));
        }
    }

    public function testThatAllEnumsCanBeFoundByOriginalName()
    {
        foreach (Timezone::getEnum() as $enum) {
            $identifier = Timezone::getFullNameByEnum($enum);

            $this->assertEquals($enum, Timezone::getEnumByOriginalName($identifier));
        }
    }

    public function testInvalidOriginalName()
    {
    	$this->expectException(\InvalidArgumentException::class);

        Timezone::getEnumByOriginalName('Horsehead_Nebula/Magrathea');
    }

}
