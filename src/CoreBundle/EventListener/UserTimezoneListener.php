<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use App\Entity\Account;
use Runalyze\Parameter\Application\Timezone;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserTimezoneListener
{
    /** @var TokenStorageInterface */
    private $token;

    public function __construct(TokenStorageInterface $token)
    {
        $this->token = $token;
    }

    public function onKernelRequest()
    {
        if ($this->token->getToken()) {
            $account = $this->token->getToken()->getUser();
            $this->token->getToken()->getUser();

            if ($account instanceof Account) {
                $timezone = (int)$account->getTimezone();

                if (Timezone::isValidValue($timezone)) {
                    $timezoneName = Timezone::getFullNameByEnum((int)$timezone);

                    try {
                        new \DateTimeZone($timezoneName);
                    } catch (\Exception $e) {
                        return;
                    }

                    date_default_timezone_set($timezoneName);
                }
            }
        }
    }
}
