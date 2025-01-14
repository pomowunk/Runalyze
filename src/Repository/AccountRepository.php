<?php

namespace App\Repository;

use App\Entity\Account;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccountRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * @param string $username username
     * @return null|Account
     */
    public function findByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string|string[] $language language key(s)
     * @param bool $excluded if set, all but given languages will be selected
     * @return array account ids
     */
    public function findAllByLanguage($language, $excluded = false)
    {
        return $this->findAllByLanguageQueryBuilder($language, $excluded)->getQuery()->getResult("COLUMN_HYDRATOR");
    }

    /**
     * @param string|string[] $language language key(s)
     * @param bool $excluded if set, all but given languages will be selected
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findAllByLanguageQueryBuilder($language, $excluded = false)
    {
        if (!is_array($language)) {
            $language = [$language];
        }

        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u.id');

        if (!empty($language)) {
            $queryBuilder
                ->where('u.language '.($excluded ? 'NOT' : '').' IN (:lang)')
                ->setParameter('lang', $language, Connection::PARAM_STR_ARRAY);
        }

        return $queryBuilder;
    }

    /**
     * @param string $username username or mail
     * @return null|Account
     */
    public function loadUserByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.mail = :mail')
            ->setParameter('username', $username)
            ->setParameter('mail', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param bool $cache
     * @return mixed number of accounts
     */
    public function getAmountOfActivatedUsers($cache = true)
    {
        $query = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.activationHash is NULL')
            ->getQuery();
        if ($cache) {
            $query->enableResultCache(320);
        }
        return $query->getSingleScalarResult();
    }

    /**
     * @param int $days
     * @return array
     */
    public function deleteNotActivatedAccounts($days = 7)
    {
        $minimumAge = time() - $days * 86400;

        return $this->createQueryBuilder('u')
            ->delete()
            ->where('u.activationHash IS NOT NULL AND u.registerdate < :minimumAge')
            ->setParameter('minimumAge', $minimumAge)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array $criteria
     * @return bool
     */
    public function existsOneWith(array $criteria)
    {
        return null !== $this->findOneBy($criteria);
    }

    /**
     * @param string $deletionHash
     * @return bool true on success
     */
    public function deleteByHash($deletionHash)
    {
        /** @var null|Account $account */
        $account = $this->findOneBy([
            'deletionHash' => $deletionHash
        ]);

        if (null !== $account) {
            $this->_em->remove($account);
            $this->_em->flush();

            return true;
        }

        return false;
    }

    /**
     * @param string $activationHash
     * @return bool true on success
     */
    public function activateByHash($activationHash)
    {
        /** @var null|Account $account */
        $account = $this->findOneBy([
            'activationHash' => $activationHash
        ]);

        if (null !== $account) {
            $this->save($account->removeActivationHash());

            return true;
        }

        return false;
    }

    public function save(Account $account)
    {
        $this->_em->persist($account);
        $this->_em->flush();
    }
}
