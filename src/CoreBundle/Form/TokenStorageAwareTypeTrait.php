<?php

namespace Runalyze\Bundle\CoreBundle\Form;

use App\Entity\Account;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trait TokenStorageAwareTypeTrait
{
    /** @var TokenStorageInterface */
    protected $TokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->TokenStorage = $tokenStorage;
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        $account = $this->TokenStorage->getToken() ? $this->TokenStorage->getToken()->getUser() : null;

        if (!($account instanceof Account)) {
            throw new \RuntimeException('Activity type must have a valid account token.');
        }

        return $account;
    }
}
