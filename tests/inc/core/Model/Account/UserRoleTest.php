<?php

namespace Runalyze\Model\Account;

use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{

	public function testCheckRoleName()
	{
        $this->assertEquals( 'ROLE_USER', UserRole::getRoleName(1));
        $this->assertEquals( 'ROLE_ADMIN', UserRole::getRoleName(2));
	}
}
