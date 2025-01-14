<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Raceresult;
use App\Entity\Sport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RaceresultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Raceresult::class);
    }

    /** @var int */
    const NUMBER_OF_RACES_TO_CONSIDER_FOR_CORRECTION_FACTOR = 3;

    /**
     * @param int $activityId
     * @return null|Raceresult
     */
    public function findByActivity($activityId)
    {
        return $this->findOneBy([
            'activity' => $activityId
        ]);
    }

    /**
     * @param int $activityId
     * @param int $accountId
     * @return null|Raceresult
     */
    public function findForAccount($activityId, $accountId)
    {
        return $this->findOneBy([
            'activity' => $activityId,
            'account' => $accountId
        ]);
    }

    /**
     * @param Account $account
     * @param Sport $sport
     * @param int $year
     * @return array
     */
    public function findBySportAndYear(Account $account, Sport $sport, $year)
    {
        return $this->createQueryBuilder('r')
            ->select(
                'r',
                't.time'
            )
            ->join('r.activity', 't')
            ->where('r.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.time BETWEEN :startTime and :endTime')
            ->setParameters([
                ':account' => $account->getId(),
                ':sport' => $sport->getId(),
                ':startTime' => mktime(0, 0, 0, 1, 1, $year),
                ':endTime' => mktime(23, 59, 59, 12, 31, $year)
            ])
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param Account $account
     * @return Raceresult[]
     */
    public function findAllWithActivityStats(Account $account)
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->addSelect('partial a.{id, time, sport, vo2max, vo2maxByTime, vo2maxWithElevation}')
            ->join('r.activity', 'a')
            ->where('r.account = :account')
            ->setParameters([
                ':account' => $account
            ])
            ->getQuery()
            ->getResult();
    }

    public function getEffectiveVO2maxCorrectionFactor(Account $account, int $sportId): float
    {
        $result = $this->createQueryBuilder('r')
            ->join('r.activity', 't')
            ->select([
                't.vo2maxByTime * 1.0 / t.vo2max as factor'
            ])
            ->where('r.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.useVO2max = 1')
            ->andWhere('t.vo2max > 0')
            ->setParameter('account', $account->getId())
            ->setParameter('sport', $sportId)
            ->orderBy('t.vo2maxByTime', 'DESC')
            ->setMaxResults(self::NUMBER_OF_RACES_TO_CONSIDER_FOR_CORRECTION_FACTOR)
            ->getQuery()
            ->getResult("COLUMN_HYDRATOR");

        if (empty($result)) {
            return 1.0;
        }

        return max(array_map(function($v) {
            return (float)$v;
        }, $result));
    }

    public function save(Raceresult $raceResult)
    {
        $this->_em->persist($raceResult);
        $this->_em->flush();
    }

    public function delete(Raceresult $raceResult)
    {
        $this->_em->remove($raceResult);
        $this->_em->flush();
    }
}
