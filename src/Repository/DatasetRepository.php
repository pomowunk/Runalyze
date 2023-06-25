<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Dataset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DatasetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dataset::class);
    }

    /**
     * @param Account $account
     * @return Dataset[]
     */
    public function findAllFor(Account $account)
    {
        return $this->findBy(
            ['account' => $account->getId()],
            ['position' => 'ASC']
        );
    }

    public function save(Dataset $dataset)
    {
        $this->_em->persist($dataset);
        $this->_em->flush();
    }

    public function remove(Dataset $dataset)
    {
        $this->_em->remove($dataset);
        $this->_em->flush();
    }
}
