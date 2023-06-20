<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Doctrine\Persistence\ManagerRegistry;

class SwimdataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Swimdata::class);
    }

    /**
     * @param int $activityId
     * @return null|Swimdata
     */
    public function findByActivity($activityId)
    {
        return $this->findOneBy([
            'activity' => $activityId
        ]);
    }
}
