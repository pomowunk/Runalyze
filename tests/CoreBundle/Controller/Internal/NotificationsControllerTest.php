<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\TemplateBasedMessage;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

/**
 * @group requiresKernel
 * @group requiresClient
 */
class NotificationsControllerTest extends AbstractFixturesAwareWebTestCase
{
    /** @var NotificationRepository */
    protected $NotificationRepository;

    /** @var Account */
    protected $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->NotificationRepository = $this->EntityManager->getRepository('CoreBundle:Notification');
        $this->Account = $this->getDefaultAccount();
    }

    /**
     * @param Account $account
     * @return int
     */
    protected function insertMessageFor(Account $account)
    {
        $notification = Notification::createFromMessage(
            new TemplateBasedMessage('../../../tests/CoreBundle/DataFixtures/messages/test-message.yml'),
            $account
        );
        $notification->setCreatedAt(10);
        $this->NotificationRepository->save($notification);

        return $notification->getId();
    }

    public function testThatReadNotificationThrowsAccessDeniedForUnknownId()
    {
        $client = $this->makeClientWithCredentials(
            $this->Account->getUsername(),
            'defaultPassword',
        );
        $client->request('GET', '/_internal/notifications/read/314159');

        $this->assertStatusCode(404, $client);
    }

    public function testThatReadNotificationThrowsAccessDeniedForWrongUser()
    {
        $id = $this->insertMessageFor($this->Account);

        $client = $this->makeClientWithCredentials(
            $this->getEmptyAccount()->getUsername(),
            'emptyPassword'
        );
        $client->request('GET', '/_internal/notifications/read/'.$id);

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testThatReadNotificationWorksForCorrectUser()
    {
        $id = $this->insertMessageFor($this->Account);

        $client = $this->makeClientWithCredentials(
            $this->Account->getUsername(),
            'defaultPassword',
        );
        $client->request('GET', '/_internal/notifications/read/'.$id);

        $this->isSuccessful($client->getResponse());
    }

    public function testThatNewNotificationActionIsEmptyForEmptyAccount()
    {
        $this->insertMessageFor($this->Account);

        $client = $this->makeClientWithCredentials(
            $this->getEmptyAccount()->getUsername(),
            'emptyPassword'
        );
        $client->request('GET', '/_internal/notifications');

        $this->assertEquals(
            json_encode([]),
            $client->getResponse()->getContent()
        );
    }

    public function testThatNewNotificationActionReturnsCorrectTextAndLink()
    {
        $id = $this->insertMessageFor($this->Account);

        $client = $this->makeClientWithCredentials(
            $this->Account->getUsername(),
            'defaultPassword',
        );
        $client->request('GET', '/_internal/notifications');

        $this->assertEquals(
            json_encode([[
                'id' => $id,
                'link' => 'http://runalyze.com/',
                'text' => 'foobar',
                'size' => 'external',
                'createdAt' => 10
            ]]),
            $client->getResponse()->getContent()
        );
    }
}
