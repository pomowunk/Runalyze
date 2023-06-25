<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Trackdata;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrackdataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trackdata::class);
    }

    /**
     * @param int $activityId
     * @param Account $account
     * @return null|Trackdata
     */
    public function findByActivity($activityId, Account $account)
    {
        return $this->findOneBy([
            'activity' => $activityId,
            'account' => $account
        ]);
    }

    public function save(Trackdata $trackdata)
    {
        $this->_em->persist($trackdata);
        $this->_em->flush();
    }
}
