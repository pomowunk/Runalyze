<?php

namespace Runalyze\Export\File;

use PHPUnit\Framework\TestCase;
use Runalyze\View\Activity\FakeContext;

/**
 * @group dependsOn
 * @group dependsOnOldFactory
 */

class KmlTest extends TestCase
{
	public function testFileCreationForOutdoorActivity()
	{
        $this->expectNotToPerformAssertions();

		$Exporter = new Kml(FakeContext::outdoorContext());
		$Exporter->createFileWithoutDirectDownload();
	}
}
