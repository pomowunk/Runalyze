<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use App\Entity\Account;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trait TokenStorageAwareServiceTrait
{
    /** @var TokenStorageInterface */
    protected $TokenStorage;

    /**
     * @return bool
     */
    protected function knowsUser()
    {
        return null !== $this->TokenStorage->getToken() && $this->TokenStorage->getToken()->getUser() instanceof Account;
    }

    /**
     * @return Account|null
     */
    protected function getUser()
    {
        return $this->knowsUser() ? $this->TokenStorage->getToken()->getUser() : null;
    }
}
