<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use Runalyze\Parser\Activity\Common\Filter\DefaultFilterCollection;
use Runalyze\Parser\Activity\Common\Filter\FilterCollection;

class ActivityDataContainerFilter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected FilterCollection $Filter;

    public function __construct(LoggerInterface $activityUploadsLogger)
    {
        $this->logger = $activityUploadsLogger ?: new NullLogger();

        $this->initFilterCollection();
    }

    protected function initFilterCollection()
    {
        $this->Filter = new DefaultFilterCollection($this->logger);
    }

    public function filter(ActivityDataContainer $container)
    {
        $this->Filter->filter($container);
    }
}
