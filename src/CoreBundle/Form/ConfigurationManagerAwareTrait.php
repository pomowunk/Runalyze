<?php

namespace Runalyze\Bundle\CoreBundle\Form;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;

trait ConfigurationManagerAwareTrait
{
    /** @var ConfigurationManager */
    protected $ConfigurationManager;

    /** @required */
    public function setConfigurationManager(ConfigurationManager $manager)
    {
        $this->ConfigurationManager = $manager;
    }

    /**
     * @return RunalyzeConfigurationList
     */
    public function getConfigurationList()
    {
        return $this->ConfigurationManager->getList();
    }
}
