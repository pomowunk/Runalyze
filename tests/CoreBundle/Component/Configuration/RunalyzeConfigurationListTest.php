<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Configuration;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;

class RunalyzeConfigurationListTest extends TestCase
{
    public function testConstructor()
    {
        $this->expectNotToPerformAssertions();

        new RunalyzeConfigurationList();
    }

    public function testThatAllCategoriesAreAccessible()
    {
        $this->expectNotToPerformAssertions();

        $config = new RunalyzeConfigurationList();

        $config->getActivityForm();
        $config->getActivityView();
        $config->getBasicEndurance();
        $config->getData();
        $config->getDataBrowser();
        $config->getDesign();
        $config->getGeneral();
        $config->getPrivacy();
        $config->getTrimp();
        $config->getVO2maxCorrectionFactor();
    }

    public function testThatUnitSystemIsPersistent()
    {
        $config = new RunalyzeConfigurationList();

        $this->assertEquals($config->getUnitSystem(), $config->getUnitSystem());
    }
}
