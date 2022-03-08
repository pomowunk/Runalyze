<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @author https://github.com/avanzu/Symfony-Doctrine-Prefix-Bundle/blob/master/Subscriber/TablePrefixSubscriber.php
 */
class TablePrefixSubscriber implements \Doctrine\Common\EventSubscriber
{
    /** @var string */
    protected $databasePrefix;

    public function __construct(string $databasePrefix)
    {
        $this->databasePrefix = $databasePrefix;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var $classMetadata \Doctrine\ORM\Mapping\ClassMetadata */
        $classMetadata = $eventArgs->getClassMetadata();

        if (strlen($this->databasePrefix)) {
            if (0 !== strpos($classMetadata->getTableName(), $this->databasePrefix)) {
                $classMetadata->setPrimaryTable([
                    'name' => $this->databasePrefix.$classMetadata->getTableName()
                ]);
            }
        }

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY) {
                if (
                    !isset($classMetadata->associationMappings[$fieldName]['joinTable']) ||
                    !isset($classMetadata->associationMappings[$fieldName]['joinTable']['name'])
                ) {
                    continue;
                }

                $mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];

                if (0 !== strpos($mappedTableName, $this->databasePrefix)) {
                    $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->databasePrefix.$mappedTableName;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array('loadClassMetadata');
    }
}
