<?php

namespace App\Controller\Internal;

use App\Entity\Account;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Component\Notifications\MessageFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/_internal/notifications")
 */
class NotificationsController extends AbstractController
{
    protected NotificationRepository $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository) {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * @Route("/read/all", name="internal-notifications-read-all")
     * @Security("is_granted('ROLE_USER')")
     */
    public function readAllNotificationsAction(Account $account): Response
    {
        $this->notificationRepository->markAllAsRead($account);

        return new JsonResponse();
    }

    /**
     * @Route("/read/{id}", name="internal-notifications-read")
     * @ParamConverter("notification", class="App\Entity\Notification")
     * @Security("is_granted('ROLE_USER')")
     */
    public function readNotificationAction(Notification $notification, Account $account): Response
    {
        if ($notification->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException(); // use 403 not found, for privacy
        }

        $this->notificationRepository->markAsRead($notification);

        return new JsonResponse();
    }

    /**
     * @Route("", name="internal-notifications-list")
     * @Security("is_granted('ROLE_USER')")
     */
    public function newNotificationsAction(
        Request $request, 
        Account $account, 
        RouterInterface $router, 
        TranslatorInterface $translator
    ): Response
    {
        $messages = [];
        $factory = new MessageFactory();
        $notifications = $this->notificationRepository->findAllSince($request->query->getInt('last_request'), $account);

        foreach ($notifications as $notification) {
            $message = $factory->getMessage($notification);
            $messages[] = [
                'id' => $notification->getId(),
                'link' => $message->hasLink() ? $message->getLink($router) : '',
                'text' => $message->getText($translator),
                'size' => $message->isLinkInternal() ? $message->getWindowSizeForInternalLink() : 'external',
                'createdAt' => $notification->getCreatedAt()
            ];
        }

        return new JsonResponse($messages);
    }
}
