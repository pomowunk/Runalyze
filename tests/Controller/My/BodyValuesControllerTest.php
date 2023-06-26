<?php

namespace App\Tests\Controller\My;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use RuntimeException;

/**
 * @group requiresKernel
 * @group requiresClient
 */
class BodyValuesControllerTest extends AbstractFixturesAwareWebTestCase
{
    /** @var Account */
    protected $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    public function testAddAction()
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/add');

        $this->assertStatusCode(200, $client);
    }

    public function testTableAction()
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/table');

        $this->assertStatusCode(200, $client);
    }

}
