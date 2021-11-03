<?php

namespace Runalyze\Export\File;

use PHPUnit\Framework\TestCase;
use Runalyze\View\Activity\FakeContext;

class FitlogTest extends TestCase
{
	public function testFileCreationForEmptyContext()
	{
        $this->expectNotToPerformAssertions();

		$Exporter = new Fitlog(FakeContext::emptyContext());
		$Exporter->createFileWithoutDirectDownload();
	}

	public function testFileCreationForIndoorActivity()
	{
        $this->expectNotToPerformAssertions();

		$Exporter = new Fitlog(FakeContext::indoorContext());
		$Exporter->createFileWithoutDirectDownload();
	}

	public function testFileCreationForOutdoorActivity()
	{
        $this->expectNotToPerformAssertions();

		$Exporter = new Fitlog(FakeContext::outdoorContext());
		$Exporter->createFileWithoutDirectDownload();
	}
}
