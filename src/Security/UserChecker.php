<?php

namespace App\Security;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Account) {
            return;
        }

        if (!empty($user->getActivationHash())) {
            // the message passed to this exception is meant to be displayed to the user
            throw new DisabledException('Your user account is not activated yet.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof Account) {
            return;
        }
    }
}