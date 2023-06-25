<?php

namespace App\Controller;

use App\Entity\Account;
use App\Repository\NotificationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/notifications")
 */
class NotificationsController extends AbstractController
{
    /**
     * @Route("", name="notifications-list")
     * @Security("is_granted('ROLE_USER')")
     */
    public function newNotificationsAction(
        Account $account,
        RouterInterface $router,
        TranslatorInterface $translator,
        NotificationRepository $notificationRepository,
    ): Response
    {
        return $this->render('notifications.html.twig', [
            'notifications' => $notificationRepository->findAll($account),
            'router' => $router,
            'translator' => $translator
        ]);
    }
}
