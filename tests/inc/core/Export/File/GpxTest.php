<?php

namespace Runalyze\Export\File;

use PHPUnit\Framework\TestCase;
use Runalyze\View\Activity\FakeContext;

class GpxTest extends TestCase
{
	public function testFileCreationForOutdoorActivity()
	{
        $this->expectNotToPerformAssertions();

		$Exporter = new Gpx(FakeContext::outdoorContext());
		$Exporter->createFileWithoutDirectDownload();
	}
}
