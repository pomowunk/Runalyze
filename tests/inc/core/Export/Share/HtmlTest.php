<?php

namespace Runalyze\Export\Share;

use PHPUnit\Framework\TestCase;
use Runalyze\View\Activity\FakeContext;
use Runalyze\Model\Activity;

/**
 * @group dependsOn
 * @group dependsOnOldFactory
 */

class HtmlTest extends TestCase
{
	public function checkThatItsAlwaysPossible()
	{
		$this->assertTrue((new Html(FakeContext::onlyWithActivity(
			new Activity\Entity(array(
				Activity\Entity::IS_PUBLIC => false
			))
		)))->isPossible());

		$this->assertTrue((new Html(FakeContext::onlyWithActivity(
			new Activity\Entity(array(
				Activity\Entity::IS_PUBLIC => true
			))
		)))->isPossible());
	}

    public function testThatCodeCanBeCreated()
    {
		$this->expectNotToPerformAssertions();

        ob_start();

		foreach (FakeContext::examplaryContexts() as $context) {
			$Sharer = new Html($context);
			$Sharer->display();
		}

		ob_end_clean();
    }
}
