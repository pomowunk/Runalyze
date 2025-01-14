<?php

namespace Runalyze\Bundle\CoreBundle\Services\Recalculation\Task;

use App\Entity\Account;

trait AccountAwareTaskTrait
{
    /** @var Account|null */
    protected $Account;

    public function setAccount(Account $account)
    {
        $this->Account = $account;
    }
}
