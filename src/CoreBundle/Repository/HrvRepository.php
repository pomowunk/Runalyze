<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Doctrine\Persistence\ManagerRegistry;

class HrvRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hrv::class);
    }

    /**
     * @param int $activityId
     * @return null|Hrv
     */
    public function findByActivity($activityId)
    {
        return $this->findOneBy([
            'activity' => $activityId
        ]);
    }
}
