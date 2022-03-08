<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/notifications")
 */
class NotificationsController extends Controller
{
    /**
     * @Route("", name="notifications-list")
     * @Security("has_role('ROLE_USER')")
     */
    public function newNotificationsAction(
        Account $account,
        RouterInterface $router,
        TranslatorInterface $translator,
        NotificationRepository $notificationRepository)
    {
        return $this->render('notifications.html.twig', [
            'notifications' => $notificationRepository->findAll($account),
            'router' => $router,
            'translator' => $translator
        ]);
    }
}
