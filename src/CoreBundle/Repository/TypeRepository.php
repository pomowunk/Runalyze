<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TypeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Type::class);
    }

    /**
     * @param Account $account
     * @param Sport|null $sport
     * @return Type[]
     */
    public function findAllFor(Account $account, Sport $sport = null)
    {
        if (null !== $sport) {
            return $this->findBy([
                'account' => $account->getId(),
                'sport' => $sport->getId()
            ]);
        }

        return $this->findBy([
            'account' => $account->getId()
        ]);
    }

    /**
     * @param string $typeName
     * @param Account $account
     * @return null|Type
     */
    public function findByNameFor($typeName, Account $account)
    {
        return $this->findOneBy([
            'account' => $account->getId(),
            'name' => (string)$typeName
        ]);
    }

    public function save(Type $type)
    {
        $this->_em->persist($type);
        $this->_em->flush();
    }
}
