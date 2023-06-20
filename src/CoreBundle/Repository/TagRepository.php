<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @param Account $account
     * @return Tag[]
     */
    public function findAllFor(Account $account)
    {
        return $this->findBy([
            'account' => $account->getId()
        ],
            ['tag' => 'ASC']);
    }

    public function save(Tag $tag)
    {
        $this->_em->persist($tag);
        $this->_em->flush();
    }

    public function remove(Tag $tag)
    {
        $this->_em->remove($tag);
        $this->_em->flush();
    }
}
