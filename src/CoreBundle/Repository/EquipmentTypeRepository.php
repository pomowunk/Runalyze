<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Symfony\Bridge\Doctrine\RegistryInterface;

class EquipmentTypeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, EquipmentType::class);
    }

    /**
     * @param Account $account
     * @return EquipmentType[]
     */
    public function findAllFor(Account $account)
    {
        return $this->findBy([
            'account' => $account->getId()
        ]);
    }

    /**
     * @param Account $account
     * @return EquipmentType[]
     */
    public function findSingleChoiceTypesFor(Account $account)
    {
        return $this->findBy([
            'input' => EquipmentType::CHOICE_SINGLE,
            'account' => $account->getId()
        ]);
    }

    public function save(EquipmentType $equipmentType)
    {
        $this->_em->persist($equipmentType);
        $this->_em->flush();
    }

    public function remove(EquipmentType $equipmentType)
    {
        $this->_em->remove($equipmentType);
        $this->_em->flush();
    }
}
