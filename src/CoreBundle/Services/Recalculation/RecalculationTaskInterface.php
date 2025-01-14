<?php

namespace Runalyze\Bundle\CoreBundle\Services\Recalculation;

use App\Entity\Account;

interface RecalculationTaskInterface
{
    public function setAccount(Account $account);

    public function run();

    /**
     * @return int
     */
    public function getOrder();
}
