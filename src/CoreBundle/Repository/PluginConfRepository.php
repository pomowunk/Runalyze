<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PluginConfRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PluginConf::class);
    }

    /**
     * @param Account $account
     * @return int[]
     */
    public function getAllActivityIdsOfFunRaces(Account $account)
    {
        $result = $this->createQueryBuilder('c')
            ->innerJoin('c.plugin', 'p')
            ->select('c.value')
            ->where('p.account = :account')
            ->andWhere('p.key = :plugin')
            ->andWhere('c.config = :config')
            ->setParameter('account', $account)
            ->setParameter('plugin', 'RunalyzePluginStat_Wettkampf')
            ->setParameter('config', 'fun_ids')
            ->getQuery()
            ->getSingleScalarResult();

        return array_filter(
            array_map(function ($id) {
                return (int)trim($id);
            }, explode(',', $result))
        );
    }
}
