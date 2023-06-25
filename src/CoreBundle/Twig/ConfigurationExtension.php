<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

use App\Entity\Account;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConfigurationExtension extends AbstractExtension
{
    /** @var ConfigurationManager */
    protected $ConfigurationManager;

    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->ConfigurationManager = $configurationManager;
    }

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return 'runalyze.configuration_extension';
    }

    /**
     * @return TwigFunction[]
     *
     * @codeCoverageIgnore
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('configVar', array($this, 'configVar')),
            new TwigFunction('config', array($this, 'config')),
            new TwigFunction('unitSystem', array($this, 'unitSystem'))
        );
    }

    /**
     * Get config variable from current user
     *
     * @param string $key
     * @return mixed
     */
    public function configVar($key)
    {
        return $this->ConfigurationManager->getList()->get($key);
    }

    /**
     * @param Account|null $account
     * @return RunalyzeConfigurationList
     */
    public function config(Account $account = null)
    {
        return $this->ConfigurationManager->getList($account);
    }

    /**
     * @param Account|null $account
     * @return \Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem
     */
    public function unitSystem(Account $account = null)
    {
        return $this->ConfigurationManager->getList($account)->getUnitSystem();
    }
}
