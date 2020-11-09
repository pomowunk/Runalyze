<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

class TypeRepository extends EntityRepository
{
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
     * @param Sport|null $sport
     * @return null|Type
     */
    public function findByNameFor($typeName, Account $account, Sport $sport = null)
    {
        // #TSC add $sport as parameter - only with sport the type can be find unique
        return $this->findOneBy([
            'account' => $account->getId(),
            'name' => (string)$typeName,
            'sport' => $sport->getId()
        ]);
    }

    /**
     * #TSC add search for shurt-cut.
     *
     * @param string $shortCut
     * @param Account $account
     * @param Sport|null $sport
     * @return null|Type
     */
    public function findByShortCutFor($shortCut, Account $account, Sport $sport = null)
    {
        return $this->findOneBy([
            'account' => $account->getId(),
            'abbr' => (string)$shortCut,
            'sport' => $sport->getId()
        ]);
    }

    public function save(Type $type)
    {
        $this->_em->persist($type);
        $this->_em->flush();
    }
}
