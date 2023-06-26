<?php

namespace App\Repository;

use App\Entity\Swimdata;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
