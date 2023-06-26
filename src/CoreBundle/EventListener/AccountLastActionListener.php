<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use App\Entity\Account;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;

class AccountLastActionListener
{
    /** @var TokenStorageInterface */
    protected $token;

    /** @var EntityManager */
    protected $em;

    public function __construct(
        TokenStorageInterface $token,
        EntityManager $manager)
    {
        $this->token = $token;
        $this->em = $manager;
    }

    /**
    * Update the user "lastaction" on each request
    * @param FilterControllerEvent $event
    */
    public function onCoreController(FilterControllerEvent $event)
    {
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        if ($this->token->getToken()) {
            $account = $this->token->getToken()->getUser();
            $this->token->getToken()->getUser();

            if ($account instanceof Account && $account->getLastAction() < strtotime('2 minutes ago')) {
                $account->setLastAction();
                $this->em->persist($account);
                $this->em->flush();
            }
        }
    }
}
