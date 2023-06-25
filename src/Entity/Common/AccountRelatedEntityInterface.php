<?php

namespace App\Entity\Common;

use App\Entity\Account;

interface AccountRelatedEntityInterface
{
    public function getAccount(): ?Account;
}
