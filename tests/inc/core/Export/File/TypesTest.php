<?php

namespace Runalyze\Export\File;

use PHPUnit\Framework\TestCase;
use Runalyze\View\Activity\Context;

/**
 * @group dependsOn
 * @group dependsOnOldFactory
 */

class TypesTest extends TestCase
{
    public function testAllConstructors()
    {
        $this->expectNotToPerformAssertions();

        $context = new Context(0, 0);

        foreach (Types::getEnum() as $typeid) {
            Types::get($typeid, $context);
        }
    }
}
