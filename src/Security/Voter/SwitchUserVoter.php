<?php

namespace App\Security\Voter;

use App\Entity\Account;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class SwitchUserVoter extends Voter
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, ['CAN_SWITCH_USER']) && $subject instanceof Account;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous or if the subject is not a user, do not grant access
        if (!$user instanceof Account || !$subject instanceof Account) {
            return false;
        }

        return $this->security->isGranted('ROLE_ADMIN') && $subject->getAllowSupport();
    }
}